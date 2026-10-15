@extends('layouts.storefront')
@php($pageTitle='Pedido creado · El Nuevo Sol')
@php($noindex=true)
@section('content')
<section class="ens-confirmation ens-block"><span class="ens-kicker">¡Gracias por elegirnos!</span><h1>{{ config('demo.enabled') ? 'Pedido de prueba creado' : 'Pedido creado correctamente' }}</h1><p class="ens-order-number">#{{ $order->order_number }}</p><p>{{ $order->customer_first_name }} {{ $order->customer_last_name }} · {{ $order->customer_phone }}</p><h2>Total confirmado: {{ \App\Support\ProductDisplay::money($order->confirmed_total) }}</h2><p>+ {{ $order->items->where('requires_confirmation',true)->count() }} productos a confirmar</p>@if(config('demo.enabled'))<p>Tu pedido ficticio quedó guardado. No genera una compra, pago ni envío al comercio.</p>@else<p>Tu pedido ya quedó guardado. Continuá por WhatsApp para coordinarlo con el local.</p>@endif
@if(config('demo.enabled'))<div class="ens-notice" role="status">WhatsApp está deshabilitado durante la demostración.</div>@elseif($whatsappUrl)<a class="ens-btn ens-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Continuar en WhatsApp</a>@else<div class="ens-notice" role="status">WhatsApp todavía no está configurado. Tu pedido quedó guardado; conservá su número para consultar al local.</div><button class="ens-btn" disabled>WhatsApp no disponible</button>@endif
@can('access-account')<p><a href="{{ route('account.orders.show',$order) }}">Ver estado en Mis pedidos</a></p>@endcan @include('storefront.pickup')
<details open><summary>Detalle del pedido</summary><pre class="ens-order-message">{{ $message }}</pre></details><a class="ens-btn ens-btn-outline" href="{{ route('store.products') }}">Seguir explorando</a></section>
@endsection

