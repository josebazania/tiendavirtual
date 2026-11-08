@extends('layout')
@section('title', 'Administración')
@section('content')
<h1>Administración</h1><p class="muted">Crea cursos y organiza sus lecciones.</p>
<div class="card"><h2>Crear curso</h2><form action="{{ route('admin.courses.store') }}" method="post">@csrf
<label for="title">Título</label><input id="title" name="title" value="{{ old('title') }}" maxlength="255" required>
<label for="description">Descripción</label><textarea id="description" name="description" required>{{ old('description') }}</textarea>
<label for="price">Precio en soles</label><input id="price" name="price" type="number" min="0.01" step="0.01" value="{{ old('price') }}" required>
<p><label><input name="published" type="checkbox" value="1" @checked(old('published'))> Publicar curso</label></p><button>Crear curso</button></form></div>
<h2>Cursos</h2>@forelse($courses as $course)<article class="card"><div class="actions"><div style="flex:1"><strong>{{ $course->title }}</strong><div class="muted small">{{ $course->published ? 'Publicado' : 'Borrador' }} · S/ {{ number_format($course->price_cents / 100, 2) }}</div></div><a class="button secondary" href="{{ route('admin.courses.edit', $course) }}">Editar</a><form class="inline" method="post" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('¿Eliminar este curso y todas sus lecciones? Esta acción no se puede deshacer.')">@csrf @method('DELETE')<button class="secondary" type="submit">Eliminar</button></form></div></article>@empty<p class="muted">Todavía no has creado cursos.</p>@endforelse
<h2>Últimos pedidos</h2>@forelse($orders as $order)<article class="card"><strong>{{ $order->course->title }}</strong><div>{{ $order->user->name }} · {{ $order->user->email }}</div><div class="muted small">S/ {{ number_format($order->amount_cents / 100, 2) }} · {{ $order->status }} · {{ $order->created_at->format('d/m/Y H:i') }}</div></article>@empty<p class="muted">Aún no hay pedidos.</p>@endforelse
@endsection
