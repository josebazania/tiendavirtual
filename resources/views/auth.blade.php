@extends('layout')
@section('title', $register ? 'Crear cuenta' : 'Ingresar')
@section('content')
<div class="card form-card"><h1>{{ $register ? 'Crea tu cuenta' : 'Bienvenido de nuevo' }}</h1><p class="muted">{{ $register ? 'Comienza tu camino de aprendizaje.' : 'Ingresa para continuar aprendiendo.' }}</p><form method="post" action="{{ $register ? route('register') : route('login') }}">@csrf
@if($register)<label for="name">Nombre</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name">@endif
<label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
<label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="{{ $register ? 'new-password' : 'current-password' }}">
@if($register)<label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">@endif
<p><button type="submit">{{ $register ? 'Crear cuenta' : 'Ingresar' }}</button></p></form><p>@if($register)¿Ya tienes cuenta? <a href="{{ route('login') }}">Ingresa</a>@else<a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a><br>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a>@endif</p></div>
@endsection
