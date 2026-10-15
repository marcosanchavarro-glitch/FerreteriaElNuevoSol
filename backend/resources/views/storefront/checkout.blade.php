@extends('layouts.storefront')
@php($pageTitle='Revisá tu pedido · El Nuevo Sol')
@php($noindex=true)
@section('content')
<div class="ens-page-head"><div><span class="ens-kicker">Un paso más</span><h1>Revisá tu pedido</h1></div><a href="{{ route('cart.index') }}">← Volver al carrito</a></div>
<div class="ens-checkout-grid"><section class="ens-checkout-contact ens-block"><h2>Tus datos para este pedido</h2><p>No necesitás una cuenta. Si ya ingresaste, podés corregir estos datos sin modificar tu perfil.</p>
<form method="POST" action="{{ route('checkout.store') }}" data-checkout-form>@csrf<input type="hidden" name="token" value="{{ $token }}">
<div class="ens-contact-fields">
@foreach(['first_name'=>'Nombre','last_name'=>'Apellido','phone'=>'Teléfono','email'=>'Email (opcional)'] as $field=>$label)
<div class="ens-field"><label for="contact-{{ $field }}">{{ $label }}</label><input id="contact-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" autocomplete="{{ ['first_name'=>'given-name','last_name'=>'family-name','phone'=>'tel','email'=>'email'][$field] }}" maxlength="{{ $field === 'email' ? 254 : ($field === 'phone' ? 30 : 100) }}" value="{{ old($field,auth()->user()?->{$field}) }}" @required($field !== 'email')></div>
@endforeach
</div>
@include('storefront.pickup')
<p class="ens-muted">Al confirmar se guardará tu pedido. Después podrás continuar por WhatsApp para coordinarlo con el local.</p>
<button type="submit" class="ens-btn ens-btn-primary">Confirmar pedido por WhatsApp</button>
</form></section>
<aside class="ens-order-summary"><h2>Tu pedido</h2>
@foreach($items as $item)<article class="ens-summary-line"><strong>{{ $item['product_name'] }}</strong>@if($item['variant_name'])<span>{{ $item['variant_name'] }}</span>@endif<span>{{ \App\Support\ProductDisplay::quantity($item['quantity']) }} {{ \App\Support\ProductDisplay::label($item['sale_unit']) }} × {{ \App\Support\ProductDisplay::money($item['unit_price']) }}</span><b>{{ \App\Support\ProductDisplay::money($item['subtotal']) }}</b></article>@endforeach
<p>Total confirmado</p><strong class="ens-summary-total">{{ \App\Support\ProductDisplay::money($total) }}</strong><p>+ {{ $pending }} productos a confirmar</p><small>Estos productos no están incluidos en el total confirmado.</small>
</aside></div>
@endsection

