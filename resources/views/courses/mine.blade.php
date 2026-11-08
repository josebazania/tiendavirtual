@extends('layout')
@section('title', 'Mis cursos')
@section('content')
<h1>Mis cursos</h1><p class="muted">Continúa aprendiendo a tu ritmo.</p><div class="grid">@forelse($courses as $course)<article class="card"><h2>{{ $course->title }}</h2><a class="button" href="{{ route('courses.learn', $course) }}">Ir al aula</a></article>@empty<div class="card"><p>Aún no tienes cursos inscritos.</p><a href="{{ route('home') }}">Explorar cursos →</a></div>@endforelse</div>
@endsection
