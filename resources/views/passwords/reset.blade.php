@extends('layout')
@section('title', 'Restablecer contraseña')
@section('content')
<div class="card form-card"><h1>Nueva contraseña</h1><form method="post" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" required><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><p><button>Guardar contraseña</button></p></form></div>
@endsection
