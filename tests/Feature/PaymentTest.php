<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $setting = new SiteSetting(['site_title' => 'Aula', 'smtp_scheme' => 'smtp', 'mercadopago_access_token' => 'test-token', 'mercadopago_webhook_secret' => 'test-secret']);
        $setting->id = 1;
        $setting->save();
    }

    public function test_checkout_creates_a_server_priced_order_and_redirects(): void
    {
        Http::fake(['api.mercadopago.com/v1/orders' => Http::response(['id' => 'ORD123', 'currency' => 'PEN', 'checkout_url' => 'https://www.mercadopago.com.pe/checkout/test'])]);
        $user = User::factory()->create();
        $course = Course::create(['title' => 'Curso', 'description' => 'Curso', 'price_cents' => 1550, 'published' => true]);
        $this->actingAs($user)->post(route('payments.checkout', $course), ['price' => 0.01])->assertRedirect('https://www.mercadopago.com.pe/checkout/test');
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'course_id' => $course->id, 'amount_cents' => 1550, 'provider_order_id' => 'ORD123', 'status' => 'pending']);
        Http::assertSent(fn ($request) => $request['total_amount'] === '15.50' && $request->hasHeader('X-Idempotency-Key'));
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_signed_webhook_checks_remote_order_and_is_idempotent(): void
    {
        $order = $this->order();
        Http::fake(['api.mercadopago.com/v1/orders/ORD123' => Http::response($this->remote($order, 'processed', '15.50'))]);
        $this->notify()->assertOk();
        $this->notify()->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'approved']);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_invalid_signature_or_incorrect_amount_never_grants_access(): void
    {
        $order = $this->order();
        Http::fake(['api.mercadopago.com/v1/orders/ORD123' => Http::response($this->remote($order, 'processed', '0.01'))]);
        $this->postJson('/pagos/mercadopago/notificacion?data_id=ORD123', ['type' => 'order'], ['x-request-id' => 'request', 'x-signature' => 'ts=123,v1=wrong'])->assertUnauthorized();
        $this->notify()->assertOk();
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_unpaid_or_rejected_orders_do_not_grant_access(): void
    {
        $order = $this->order();
        Http::fakeSequence()->push($this->remote($order, 'created', '0.00'))->push($this->remote($order, 'failed', '0.00'));
        $this->notify()->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->notify()->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'failed']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_remote_currency_and_total_must_match_and_return_url_cannot_confirm_payment(): void
    {
        $order = $this->order();
        $remote = $this->remote($order, 'processed', '15.50');
        $remote['currency'] = 'USD';
        Http::fake(['api.mercadopago.com/v1/orders/ORD123' => Http::response($remote)]);
        $this->actingAs($order->user)->get(route('payments.result', ['reference' => $order->reference, 'status' => 'approved']))->assertOk()->assertSee('esperando la confirmación');
        $this->notify()->assertStatus(503);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    private function order(): Order
    {
        $user = User::factory()->create();
        $course = Course::create(['title' => 'Curso', 'description' => 'Curso', 'price_cents' => 1550, 'published' => true]);

        return Order::create(['reference' => (string) Str::uuid(), 'user_id' => $user->id, 'course_id' => $course->id, 'amount_cents' => 1550, 'status' => 'pending', 'provider_order_id' => 'ORD123']);
    }

    private function remote(Order $order, string $status, string $paid): array
    {
        return ['id' => 'ORD123', 'external_reference' => $order->reference, 'currency' => 'PEN', 'total_amount' => '15.50', 'total_paid_amount' => $paid, 'status' => $status];
    }

    private function notify(): TestResponse
    {
        $hash = hash_hmac('sha256', 'id:ord123;request-id:request;ts:123;', 'test-secret');

        return $this->postJson('/pagos/mercadopago/notificacion?data.id=ORD123', ['type' => 'order'], ['x-request-id' => 'request', 'x-signature' => 'ts=123,v1='.$hash]);
    }
}
