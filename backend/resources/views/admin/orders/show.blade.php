@extends('layouts.app')
@section('title','Detalle del pedido · El Nuevo Sol')
@section('content')
<a href="{{ route('admin.orders.index') }}">← Pedidos</a><h1>Pedido {{ $order->order_number }}</h1>
<section class="card"><p>{{ $order->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }} · Retiro en local</p><p class="order-badge order-{{ $order->status->value }}">Estado: {{ $order->status->label() }}</p><h2>Cliente {{ $order->user_id ? 'registrado' : 'invitado' }}</h2><p>{{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>Teléfono: {{ $order->customer_phone }}<br>Email: {{ $order->customer_email ?? 'No indicado' }}</p></section>
<section class="card"><h2>Productos del pedido</h2>@include('storefront.order-items')</section>
<section class="card"><h2>Cambiar estado</h2><div class="actions">
@forelse($order->status->nextStates() as $next)
@if($next !== \App\Enums\OrderStatus::Cancelled)
<form method="POST" action="{{ route('admin.orders.update',$order) }}">@csrf @method('PATCH')<input type="hidden" name="expected_status" value="{{ $order->status->value }}"><input type="hidden" name="status" value="{{ $next->value }}"><button type="submit">Pasar a {{ $next->label() }}</button></form>
@else
<details class="order-cancel"><summary>Cancelar pedido</summary><p>Esta acción es definitiva: el pedido quedará cancelado y no podrá reabrirse.</p><form method="POST" action="{{ route('admin.orders.update',$order) }}">@csrf @method('PATCH')<input type="hidden" name="expected_status" value="{{ $order->status->value }}"><input type="hidden" name="status" value="cancelled"><label class="check"><input type="checkbox" name="confirm_cancel" value="1" required>Confirmo que quiero cancelar este pedido</label><button class="danger" type="submit">Confirmar cancelación</button></form></details>
@endif
@empty<p>Este pedido está en un estado final y no admite cambios.</p>@endforelse</div></section>
<section class="card"><h2>Historial</h2><ol class="order-history">@foreach($order->statusHistory as $entry)<li><time>{{ $entry->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</time> — {{ $entry->previous_status?->label() ?? 'Inicio' }} → <strong>{{ $entry->new_status->label() }}</strong><br>Responsable: {{ $entry->changedBy ? $entry->changedBy->first_name.' '.$entry->changedBy->last_name : 'Sistema / invitado' }}</li>@endforeach</ol></section>
@endsection
