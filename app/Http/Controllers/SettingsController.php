<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SettingsController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403);
    }

    public function edit(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.settings', ['settings' => SiteSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'site_title' => ['required', 'string', 'max:100'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:512'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_scheme' => ['required', 'in:smtp,smtps'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mercadopago_access_token' => ['nullable', 'string', 'max:1000'],
            'mercadopago_webhook_secret' => ['nullable', 'string', 'max:1000'],
        ]);
        $settings = SiteSetting::firstOrNew(['id' => 1]);
        $settings->id = 1;
        foreach (['site_title', 'seo_description', 'smtp_host', 'smtp_port', 'smtp_scheme', 'smtp_username', 'mail_from_address', 'mail_from_name'] as $key) {
            $settings->{$key} = $data[$key] ?? null;
        }
        foreach (['smtp_password', 'mercadopago_access_token', 'mercadopago_webhook_secret'] as $key) {
            if ($request->boolean('clear_'.$key)) {
                $settings->{$key} = null;
            } elseif (! empty($data[$key])) {
                $settings->{$key} = $data[$key];
            }
        }
        foreach (['logo', 'favicon'] as $type) {
            $key = $type.'_path';
            if ($request->hasFile($type)) {
                $settings->{$key} = $request->file($type)->store('site', 'public');
                if (! $settings->{$key}) {
                    return back()->withErrors([$type => 'No se pudo guardar la imagen.']);
                }
            } elseif ($request->boolean('remove_'.$type)) {
                $settings->{$key} = null;
            }
        }
        $settings->save();

        return redirect()->route('admin.settings.edit')->with('success', 'Configuración guardada.');
    }

    public function image(string $type): Response
    {
        $settings = SiteSetting::current();
        $path = $type === 'logo' ? $settings->logo_path : $settings->favicon_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function testMail(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $settings = SiteSetting::current();
        if (! $settings->configureMail()) {
            return back()->withErrors(['email' => 'Guarda primero el servidor, puerto y remitente SMTP.']);
        }
        try {
            Mail::raw('Este mensaje confirma que la configuración de correo de la tienda funciona.', function ($message) use ($data): void {
                $message->to($data['email'])->subject('Prueba de correo de la tienda');
            });
        } catch (Throwable $exception) {
            return back()->withErrors(['email' => 'No se pudo enviar el correo. Revisa los datos SMTP y la conexión del servidor.']);
        }

        return back()->with('success', 'Correo de prueba enviado. Revisa también la carpeta de spam.');
    }
}
