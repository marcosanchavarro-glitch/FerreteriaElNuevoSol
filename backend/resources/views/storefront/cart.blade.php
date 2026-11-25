@extends('layouts.storefront')
@php($pageTitle='Tu carrito · El Nuevo Sol')
@php($noindex=true)
@section('content')
<div class="ens-page-head"><div><span class="ens-kicker">Tu próxima compra</span><h1>Tu carrito</h1></div><a href="{{ route('store.products') }}">Seguir comprando →</a></div>
@include('storefront.cart-content')
@endsection
