<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_see_or_change_settings(): void
    {
        $user = User::factory()->create();
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'), [])->assertForbidden();
        $this->post(route('admin.settings.test-mail'), ['email' => 'test@example.com'])->assertForbidden();
    }

    public function test_secrets_are_encrypted_preserved_and_not_rendered(): void
    {
        $admin = $this->admin();
        $data = ['site_title' => 'Mi academia', 'seo_description' => 'Cursos en línea', 'smtp_scheme' => 'smtp', 'smtp_password' => 'clave-secreta', 'mercadopago_access_token' => 'token-secreto', 'mercadopago_webhook_secret' => 'firma-secreta'];
        $this->actingAs($admin)->put(route('admin.settings.update'), $data)->assertRedirect();
        $raw = DB::table('site_settings')->where('id', 1)->first();
        $this->assertNotSame('clave-secreta', $raw->smtp_password);
        $this->assertNotSame('token-secreto', $raw->mercadopago_access_token);
        $this->assertNotSame('firma-secreta', $raw->mercadopago_webhook_secret);
        $this->get(route('admin.settings.edit'))->assertOk()->assertDontSee('clave-secreta')->assertDontSee('token-secreto')->assertDontSee('firma-secreta');
        $this->get('/')->assertSee('Mi academia')->assertSee('Cursos en línea');
        $this->put(route('admin.settings.update'), ['site_title' => 'Otra academia', 'smtp_scheme' => 'smtp'])->assertRedirect();
        $this->assertSame('token-secreto', SiteSetting::current()->mercadopago_access_token);
        $this->put(route('admin.settings.update'), ['site_title' => 'Otra academia', 'smtp_scheme' => 'smtp', 'clear_mercadopago_access_token' => '1'])->assertRedirect();
        $this->assertNull(SiteSetting::current()->mercadopago_access_token);
    }

    public function test_test_email_uses_saved_settings(): void
    {
        Mail::shouldReceive('raw')->once()->withArgs(fn (string $text, $callback) => str_contains($text, 'configuración de correo') && $callback instanceof \Closure);
        Mail::shouldReceive('purge')->once()->with('smtp');
        $admin = $this->admin();
        $this->saveMailSettings();
        $this->actingAs($admin)->post(route('admin.settings.test-mail'), ['email' => 'destino@example.com'])->assertSessionHas('success');
    }

    public function test_password_reset_link_and_token_reset(): void
    {
        Notification::fake();
        $this->saveMailSettings();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::broker()->createToken($user);
        $this->post(route('password.update'), ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->post(route('password.update'), ['email' => $user->email, 'token' => $token, 'password' => 'another-password', 'password_confirmation' => 'another-password'])->assertSessionHasErrors('email');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function saveMailSettings(): void
    {
        $settings = new SiteSetting(['site_title' => 'Aula', 'smtp_host' => 'smtp.example.com', 'smtp_port' => 587, 'smtp_scheme' => 'smtp', 'mail_from_address' => 'hola@example.com', 'mail_from_name' => 'Aula']);
        $settings->id = 1;
        $settings->save();
    }
}
