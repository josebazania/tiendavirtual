<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function checkout(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->published, 404);
        if (DB::table('enrollments')->where('user_id', $request->user()->id)->where('course_id', $course->id)->exists()) {
            return redirect()->route('courses.learn', $course);
        }
        $settings = SiteSetting::current();
        if (! $settings->mercadopago_access_token || ! $settings->mercadopago_webhook_secret) {
            return back()->withErrors(['payment' => 'Los pagos todavía no están configurados.']);
        }
        $order = Order::create(['reference' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'course_id' => $course->id, 'amount_cents' => $course->price_cents, 'status' => 'pending']);
        $amount = number_format($order->amount_cents / 100, 2, '.', '');
        $returnUrl = route('payments.result', ['reference' => $order->reference]);

        try {
            $response = Http::withToken($settings->mercadopago_access_token)->withHeaders(['X-Idempotency-Key' => $order->reference])->timeout(15)->post('https://api.mercadopago.com/v1/orders', [
                'type' => 'online', 'processing_mode' => 'manual', 'capture_mode' => 'automatic_async',
                'total_amount' => $amount, 'external_reference' => $order->reference,
                'payer' => ['email' => $request->user()->email],
                'items' => [['title' => $course->title, 'quantity' => 1, 'unit_price' => $amount]],
                'config' => ['online' => ['success_url' => $returnUrl, 'failure_url' => $returnUrl, 'pending_url' => $returnUrl]],
            ])->throw();
            $providerId = $response->json('id');
            $url = $response->json('checkout_url');
            if (! is_string($providerId) || ! preg_match('/^[a-zA-Z0-9_-]+$/', $providerId) || ! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'www.mercadopago.com.pe' || $response->json('currency') !== 'PEN') {
                throw new \RuntimeException('Respuesta de pago inválida.');
            }
            $order->update(['provider_order_id' => $providerId]);

            return redirect()->away($url);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'No se pudo iniciar el pago. Inténtalo de nuevo más tarde.']);
        }
    }

    public function result(Request $request): View
    {
        $order = Order::where('reference', $request->query('reference'))->where('user_id', $request->user()->id)->firstOrFail();

        return view('payments.result', compact('order'));
    }

    public function webhook(Request $request): Response
    {
        $id = (string) $request->query('data_id', '');
        $settings = SiteSetting::current();
        $secret = $settings->mercadopago_webhook_secret;
        $signature = [];
        foreach (explode(',', (string) $request->header('x-signature')) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $signature[$pair[0]] = $pair[1];
            }
        }
        $requestId = (string) $request->header('x-request-id');
        if (! $secret || ! preg_match('/^[a-zA-Z0-9_-]+$/', $id) || ! $requestId || empty($signature['ts']) || empty($signature['v1'])) {
            return response('', 401);
        }
        $manifest = 'id:'.strtolower($id).';request-id:'.$requestId.';ts:'.$signature['ts'].';';
        if (! hash_equals(hash_hmac('sha256', $manifest, $secret), $signature['v1'])) {
            return response('', 401);
        }
        if ($request->input('type') !== 'order') {
            return response('', 200);
        }
        if (! $settings->mercadopago_access_token) {
            return response('', 503);
        }
        try {
            $remote = Http::withToken($settings->mercadopago_access_token)->timeout(15)->get('https://api.mercadopago.com/v1/orders/'.$id)->throw()->json();
            if (! is_array($remote) || ($remote['id'] ?? null) !== $id || ! is_string($remote['external_reference'] ?? null)) {
                throw new \RuntimeException('Respuesta de orden inválida.');
            }
            DB::transaction(function () use ($remote, $id): void {
                $order = Order::where('reference', $remote['external_reference'])->lockForUpdate()->first();
                if (! $order || ($order->provider_order_id && $order->provider_order_id !== $id)) {
                    return;
                }
                $expected = number_format($order->amount_cents / 100, 2, '.', '');
                if (($remote['currency'] ?? null) !== 'PEN' || ! is_numeric($remote['total_amount'] ?? null) || number_format((float) $remote['total_amount'], 2, '.', '') !== $expected) {
                    throw new \RuntimeException('La moneda o el importe no coinciden con el pedido.');
                }
                $order->provider_order_id = $id;
                if (($remote['status'] ?? null) === 'processed' && is_numeric($remote['total_paid_amount'] ?? null) && number_format((float) $remote['total_paid_amount'], 2, '.', '') === $expected) {
                    $order->status = 'approved';
                    DB::table('enrollments')->insertOrIgnore(['user_id' => $order->user_id, 'course_id' => $order->course_id, 'created_at' => now(), 'updated_at' => now()]);
                } elseif ($order->status !== 'approved') {
                    $order->status = in_array($remote['status'] ?? null, ['failed', 'canceled', 'expired'], true) ? $remote['status'] : 'pending';
                }
                $order->save();
            });

            return response('', 200);
        } catch (Throwable $exception) {
            report($exception);

            return response('', 503);
        }
    }
}
