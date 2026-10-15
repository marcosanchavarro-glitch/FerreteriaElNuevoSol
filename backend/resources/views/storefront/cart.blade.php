@extends('layouts.storefront')
@php($pageTitle='Tu carrito · El Nuevo Sol')
@php($noindex=true)
@section('content')
<div class="ens-page-head"><div><span class="ens-kicker">Tu próxima compra</span><h1>Tu carrito</h1></div><a href="{{ route('store.products') }}">Seguir comprando →</a></div>
@if(!$items)
<div class="ens-empty"><h2>Tu carrito está vacío.</h2><p>Encontrá lo que necesitás para tu próximo proyecto.</p><a class="ens-btn ens-btn-primary" href="{{ route('store.products') }}">Ver productos</a></div>
@else
<div class="ens-checkout-grid"><section class="ens-cart-lines" aria-label="Productos del carrito">
@foreach($items as $key=>$item)
<article class="ens-cart-line">
@if(isset($item['error']))
<div class="ens-notice ens-error" role="alert">{{ $item['error'] }}</div>
@else
<img class="ens-cart-image" src="{{ $item['image'] }}" alt="" width="100" height="100">
<div class="ens-cart-description"><h2><a href="{{ route('store.product',$item['slug']) }}">{{ $item['product_name'] }}</a></h2>@if($item['variant_name'])<p>{{ $item['variant_name'] }}</p>@endif<p>{{ \App\Support\ProductDisplay::label($item['sale_unit']) }} · {{ $item['requires_confirmation'] ? 'Precio a confirmar' : \App\Support\ProductDisplay::money($item['unit_price']) }}</p><strong>Subtotal: {{ \App\Support\ProductDisplay::money($item['subtotal']) }}</strong></div>
@endif
<form method="POST" action="{{ route('cart.update',$key) }}" class="ens-cart-update">@csrf @method('PATCH')
<label for="qty-{{ $key }}">Cantidad</label>
<div class="ens-quantity" data-quantity><button type="button" data-delta="-1" aria-label="Disminuir cantidad">−</button><input id="qty-{{ $key }}" name="quantity" inputmode="decimal" value="{{ \App\Support\ProductDisplay::quantity($item['quantity']) }}" data-step="{{ !isset($item['error']) && !\App\Services\Storefront\Cart::fractional($item['sale_unit']) ? '1000' : '1' }}" required aria-describedby="qty-help-{{ $key }}"><button type="button" data-delta="1" aria-label="Aumentar cantidad">+</button></div><small id="qty-help-{{ $key }}">Hasta 3 decimales según unidad.</small><button class="ens-btn ens-btn-outline" type="submit">Actualizar</button></form>
<form method="POST" action="{{ route('cart.destroy',$key) }}">@csrf @method('DELETE')<button class="ens-remove" type="submit">Eliminar</button></form>
</article>
@endforeach
</section><aside class="ens-order-summary"><h2>Resumen</h2><p>{{ count($items) }} productos distintos</p><p>Total confirmado</p><strong class="ens-summary-total">{{ \App\Support\ProductDisplay::money($total) }}</strong><p>+ {{ $pending }} productos a confirmar</p><p class="ens-muted">Retiro en local. La disponibilidad se vuelve a validar al confirmar. Los productos a confirmar no están incluidos en este importe.</p>@if(collect($items)->contains(fn($item)=>isset($item['error'])))<p role="status">Corregí las líneas indicadas para continuar.</p>@else<a class="ens-btn ens-btn-primary" href="{{ route('checkout.create') }}">Revisar y continuar</a>@endif</aside></div>
@endif
@endsection

