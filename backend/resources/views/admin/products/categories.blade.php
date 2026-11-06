@extends('layouts.app')
@section('title','Categorías · '.$product->name)
@section('content')<h1>Categorías del producto</h1><p class="subtitle">{{ $product->name }}</p>@include('admin.products.sections')
@include('admin.products.categories-panel')
@endsection
