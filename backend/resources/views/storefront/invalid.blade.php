@extends('layouts.storefront')
@php($pageTitle='Revisá tu búsqueda · El Nuevo Sol')
@php($noindex=true)
@section('content')<section class="ens-empty ens-section"><h1>Revisá tu búsqueda</h1><p>Algún filtro tiene un valor no válido. Volvé al catálogo y elegí las opciones disponibles.</p><a class="ens-btn ens-btn-primary" href="{{ route('store.products') }}">Volver al catálogo</a></section>@endsection
