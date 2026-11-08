@extends('layout')
@section('title', 'Explora cursos')
@section('content')
<section class="hero"><span>APRENDE A TU RITMO</span><h1>Una nueva habilidad empieza aquí.</h1><p>Explora cursos y encuentra tu próximo paso.</p></section>
<h2>Explora nuestros cursos</h2>
<div class="grid">@forelse($courses as $course)<article class="card"><h3><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></h3><p>{{ \Illuminate\Support\Str::limit($course->description, 150) }}</p><p class="price">S/ {{ number_format($course->price_cents / 100, 2) }}</p><a href="{{ route('courses.show', $course) }}">Ver curso →</a></article>@empty<p class="muted">Pronto encontrarás cursos disponibles aquí.</p>@endforelse</div>
@endsection
