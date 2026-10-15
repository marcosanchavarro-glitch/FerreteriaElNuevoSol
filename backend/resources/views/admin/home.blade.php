@extends('layouts.app')
@section('title', 'Dashboard · El Nuevo Sol')
@section('content')
<div class="toolbar"><div><h1>Dashboard</h1><p class="subtitle">Hola, {{ auth()->user()->first_name }}. Este es el estado de la ferretería.</p></div><a class="button secondary" href="{{ route('home') }}">Ver sitio</a></div>
<div class="dashboard-stats">
@foreach(['published'=>'Productos publicados','out_of_stock'=>'Productos sin stock','draft'=>'Productos en borrador','without_image'=>'Publicados sin imagen'] as $key=>$label)<section class="card stat"><span>{{ $label }}</span><strong>{{ $productStats[$key] }}</strong></section>@endforeach
@foreach(['received'=>'Pedidos recibidos','preparing'=>'En preparación','ready_for_pickup'=>'Listos para retirar'] as $key=>$label)<a class="card stat" href="{{ route('admin.orders.index',['status'=>$key]) }}"><span>{{ $label }}</span><strong>{{ $orderStats[$key] ?? 0 }}</strong></a>@endforeach
</div>
<p class="hint">Sin stock: productos publicados con stock conocido agotado, o todas sus variantes activas agotadas. No incluye disponibilidad desconocida.</p>
<section class="card"><h2>Acciones rápidas</h2><div class="actions">@can('create', \App\Models\Product::class)<a class="button" href="{{ route('admin.products.create') }}">+ Nuevo producto</a>@endcan<a class="button secondary" href="{{ route('admin.orders.index') }}">Ver pedidos</a><a class="button secondary" href="{{ route('admin.categories.index') }}">Gestionar categorías</a><a class="button secondary" href="{{ route('admin.sections.index') }}">Editar página de inicio</a></div></section>
<section class="card"><h2>Pedidos recientes</h2><div class="table-scroll"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th>Fecha</th><th>Total confirmado</th></tr></thead><tbody>
@forelse($recentOrders as $order)<tr><td><a href="{{ route('admin.orders.show',$order) }}">{{ $order->order_number }}</a></td><td>{{ $order->customer_first_name }} {{ $order->customer_last_name }}</td><td><span class="order-badge order-{{ $order->status->value }}">{{ $order->status->label() }}</span></td><td>{{ $order->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</td><td>{{ \App\Support\ProductDisplay::money($order->confirmed_total) }}@if($order->has_items_to_confirm)<br><small>Incluye productos a confirmar</small>@endif</td></tr>@empty<tr><td colspan="5">Todavía no hay pedidos.</td></tr>@endforelse
</tbody></table></div></section>
@endsection