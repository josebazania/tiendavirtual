@extends('layout')
@section('title', 'Editar curso')
@section('content')
<a href="{{ route('admin.index') }}">← Administración</a><h1>Editar curso</h1>
<div class="card"><form action="{{ route('admin.courses.update', $course) }}" method="post">@csrf @method('PUT')
<label for="title">Título</label><input id="title" name="title" value="{{ old('title', $course->title) }}" maxlength="255" required>
<label for="description">Descripción</label><textarea id="description" name="description" required>{{ old('description', $course->description) }}</textarea>
<label for="price">Precio en soles</label><input id="price" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', number_format($course->price_cents / 100, 2, '.', '')) }}" required>
<p><label><input name="published" type="checkbox" value="1" @checked(old('published', $course->published))> Publicar curso</label></p><button>Guardar curso</button></form></div>
<h2>Lecciones</h2><p class="muted">Los videos se abren en YouTube o Vimeo. El orden menor aparece primero.</p>
@foreach($course->lessons as $lesson)<div class="card"><details><summary>{{ $lesson->position }} · {{ $lesson->title }}</summary><form method="post" action="{{ route('admin.lessons.update', [$course, $lesson]) }}">@csrf @method('PUT')
<label>Título <input name="title" value="{{ $lesson->title }}" required maxlength="255"></label><label>Contenido <textarea name="content">{{ $lesson->content }}</textarea></label><label>Enlace del video <input name="video_url" type="url" value="{{ $lesson->video_url }}" placeholder="https://..."></label><label>Orden <input name="position" type="number" min="0" max="999999" value="{{ $lesson->position }}" required></label><p><button>Guardar lección</button></p></form><form method="post" action="{{ route('admin.lessons.destroy', [$course, $lesson]) }}" onsubmit="return confirm('¿Eliminar esta lección?')">@csrf @method('DELETE')<button class="secondary">Eliminar lección</button></form></details></div>@endforeach
<div class="card"><h3>Agregar lección</h3><form method="post" action="{{ route('admin.lessons.store', $course) }}">@csrf
<label for="lesson-title">Título</label><input id="lesson-title" name="title" required maxlength="255"><label for="content">Contenido</label><textarea id="content" name="content"></textarea><label for="video_url">Enlace del video</label><input id="video_url" name="video_url" type="url" placeholder="https://..."><label for="position">Orden</label><input id="position" name="position" type="number" min="0" max="999999" value="{{ $course->lessons->count() + 1 }}" required><p><button>Agregar lección</button></p></form></div>
@endsection
