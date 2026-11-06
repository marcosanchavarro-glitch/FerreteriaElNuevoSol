@extends('layouts.app')
@section('title','Variante · '.$product->name)
@section('content')<h1>{{ $variant->exists ? 'Editar variante' : 'Crear variante' }}</h1><p class="subtitle">{{ $product->name }}</p>@include('admin.products.sections')
@include('admin.variants.fields')
@endsection
