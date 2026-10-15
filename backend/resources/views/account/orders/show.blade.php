@extends('layouts.storefront')
@php($pageTitle='Mi pedido · El Nuevo Sol')
@php($noindex=true)
@section('content')
@include('account.navigation')
<a href="{{ route('account.orders.index') }}">← Mis pedidos</a>
<section class="ens-block ens-order-detail"><h1>Pedido {{ $order->order_number }}</h1><p>{{ $order->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }} · Retiro en local</p>
<p class="order-badge order-{{ $order->status->value }}">Estado: {{ $order->status->label() }}</p>
@if($order->status === \App\Enums\OrderStatus::Cancelled)
<div class="ens-notice ens-error">Este pedido fue cancelado.</div>
@else
<ol class="ens-order-progress" aria-label="Progreso del pedido">
@php($steps=[\App\Enums\OrderStatus::Received,\App\Enums\OrderStatus::Preparing,\App\Enums\OrderStatus::ReadyForPickup,\App\Enums\OrderStatus::PickedUp])
@foreach($steps as $step)<li @if($order->status===$step)aria-current="step"@endif>{{ array_search($step,$steps) < array_search($order->status,$steps) ? '✓' : ($order->status===$step ? '●' : '○') }} {{ $step->label() }} @if($order->status===$step)<strong>· Estado actual</strong>@endif</li>@endforeach
</ol>@endif
<h2>Productos de tu pedido</h2>@include('storefront.order-items')
@include('storefront.pickup')
<h2>Historial de estados</h2><ol class="ens-order-history">@foreach($order->statusHistory as $entry)<li><strong>{{ $entry->new_status->label() }}</strong><time>{{ $entry->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</time></li>@endforeach</ol>
</section>
@endsection
