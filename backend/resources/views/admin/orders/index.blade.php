@extends('layouts.app')
@section('title','Pedidos · El Nuevo Sol')
@section('content')
<h1>Pedidos</h1><p class="subtitle">Gestión de pedidos con retiro en local.</p>
<div class="order-counts">@foreach([\App\Enums\OrderStatus::Received,\App\Enums\OrderStatus::Preparing,\App\Enums\OrderStatus::ReadyForPickup] as $state)<a class="card" href="{{ route('admin.orders.index',['status'=>$state->value]) }}">{{ $state->label() }}: <strong>{{ $counts[$state->value] ?? 0 }}</strong></a>@endforeach</div>
<form class="card order-filters" method="GET" action="{{ route('admin.orders.index') }}">
<div><label for="order-q">Buscar pedido o cliente</label><input id="order-q" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="Número, nombre, teléfono o email"></div>
<div><label for="order-status">Estado</label><select id="order-status" name="status"><option value="">Todos</option>@foreach(\App\Enums\OrderStatus::cases() as $state)<option value="{{ $state->value }}" @selected(($filters['status'] ?? '') === $state->value)>{{ $state->label() }}</option>@endforeach</select></div>
<div><label for="order-from">Desde</label><input id="order-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
<div><label for="order-to">Hasta</label><input id="order-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
<div><label for="order-customer">Cliente</label><select id="order-customer" name="customer"><option value="">Todos</option><option value="registered" @selected(($filters['customer'] ?? '') === 'registered')>Registrado</option><option value="guest" @selected(($filters['customer'] ?? '') === 'guest')>Invitado</option></select></div>
<button type="submit">Filtrar</button><a href="{{ route('admin.orders.index') }}">Limpiar filtros</a></form>
<section class="card"><p>{{ $orders->total() }} pedidos encontrados</p><div class="table-scroll"><table><thead><tr><th>Pedido / fecha</th><th>Cliente</th><th>Total confirmado</th><th>Estado</th><th>Modalidad</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><a href="{{ route('admin.orders.show',$order) }}">{{ $order->order_number }}</a><br>{{ $order->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</td><td>{{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>{{ $order->customer_phone }}<br><small>{{ $order->user_id ? 'Registrado' : 'Invitado' }}</small></td><td>{{ \App\Support\ProductDisplay::money($order->confirmed_total) }}@if($order->has_items_to_confirm)<br><strong>Incluye productos a confirmar</strong>@endif</td><td><span class="order-badge order-{{ $order->status->value }}">{{ $order->status->label() }}</span></td><td>Retiro en local</td></tr>@empty<tr><td colspan="5">No hay pedidos que coincidan.</td></tr>@endforelse
</tbody></table></div>@include('storefront.pagination',['paginator'=>$orders])</section>
@endsection
