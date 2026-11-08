@extends('layout')
@section('title', $course->title)
@section('content')
<a href="{{ route('courses.mine') }}">← Mis cursos</a><h1>{{ $course->title }}</h1><p class="muted">Contenido del curso</p>@forelse($course->lessons as $lesson)<article class="card"><h2>{{ $lesson->title }}</h2>@if($lesson->content)<p class="prose">{{ $lesson->content }}</p>@endif @if($lesson->video_url)<a class="button" href="{{ $lesson->video_url }}" target="_blank" rel="noopener noreferrer">Ver video ↗</a>@endif</article>@empty<p>El curso aún no tiene lecciones.</p>@endforelse
@endsection
