@extends('layout')
@section('title', 'Recuperar contraseña')
@section('content')
<div class="card form-card"><h1>Recuperar contraseña</h1><p>Te enviaremos un enlace para crear una contraseña nueva.</p><form method="post" action="{{ route('password.email') }}">@csrf<label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required><p><button>Enviar enlace</button></p></form><a href="{{ route('login') }}">Volver a ingresar</a></div>
@endsection
