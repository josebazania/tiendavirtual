@extends('layout')
@section('title', $course->title)
@section('content')
<a href="{{ route('home') }}">← Todos los cursos</a><div class="card" style="margin-top:1rem"><p class="muted">CURSO EN LÍNEA</p><h1>{{ $course->title }}</h1><p class="prose">{{ $course->description }}</p><p class="price">S/ {{ number_format($course->price_cents / 100, 2) }}</p>@guest<a class="button" href="{{ route('login') }}">Ingresa para comprar</a><p>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>@else @if($enrolled)<a class="button" href="{{ route('courses.learn', $course) }}">Ir al aula</a>@else<form method="post" action="{{ route('payments.checkout', $course) }}">@csrf<button type="submit">Comprar con Mercado Pago</button></form>@endif @endguest</div>
@endsection
