@extends('layouts.app')
@section('title','Imágenes · '.$product->name)
@section('content')<h1>Imágenes</h1><p class="subtitle">{{ $product->name }}</p>@include('admin.products.sections')
@include('admin.products.images-panel')
@endsection
