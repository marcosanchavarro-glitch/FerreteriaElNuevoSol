@extends('layouts.storefront')
@php($pageTitle='Página no encontrada · El Nuevo Sol')
@php($noindex=true)
@section('content')<section class="ens-empty ens-section"><h1>No encontramos esta página</h1><p>El enlace no está disponible o no tenés acceso a esta información.</p><a class="ens-btn ens-btn-primary" href="{{ route('store.products') }}">Explorar productos</a></section>@endsection

