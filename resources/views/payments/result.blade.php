@extends('layout')
@section('title', 'Estado de tu compra')
@section('content')
<div class="card"><h1>Estado de tu compra</h1>@if($order->status === 'approved')<p>Tu pago fue confirmado y ya puedes acceder al curso.</p><a class="button" href="{{ route('courses.learn', $order->course_id) }}">Ir al aula</a>@elseif(in_array($order->status, ['failed', 'canceled', 'expired'], true))<p>El pago no se completó. Puedes volver a intentar la compra.</p><a class="button" href="{{ route('courses.show', $order->course_id) }}">Volver al curso</a>@else<p>Estamos esperando la confirmación de Mercado Pago. Esta página no confirma el pago por sí sola.</p><p>Actualiza esta página en unos momentos o consulta tus cursos más tarde.</p><a href="{{ route('courses.mine') }}">Mis cursos</a>@endif</div>
@endsection
