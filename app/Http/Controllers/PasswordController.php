<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('passwords.email');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email']]);
        if (! SiteSetting::current()->configureMail()) {
            return back()->withErrors(['email' => 'La recuperación por correo no está configurada. Contacta al administrador.']);
        }
        try {
            Password::sendResetLink($credentials);
        } catch (Throwable $exception) {
            return back()->withErrors(['email' => 'No se pudo enviar el correo. Inténtalo más tarde.']);
        }

        return back()->with('success', 'Si la cuenta existe, recibirás un enlace para restablecer tu contraseña.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('passwords.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'token' => ['required', 'string'], 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $status = Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace no es válido o ha caducado. Solicita uno nuevo.'])->onlyInput('email');
        }

        return redirect()->route('login')->with('success', 'Tu contraseña se ha actualizado. Ya puedes ingresar.');
    }
}
