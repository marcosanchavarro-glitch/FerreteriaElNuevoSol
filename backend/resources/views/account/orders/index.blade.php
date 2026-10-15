@extends('layouts.storefront')
@php($pageTitle='Mis pedidos · El Nuevo Sol')
@php($noindex=true)
@section('content')
@include('account.navigation')
<h1>Mis pedidos</h1><p>Consultá el estado y los detalles de tus compras con retiro en local.</p>
@include('account.orders.list')
@include('storefront.pagination',['paginator'=>$orders])
@endsection
