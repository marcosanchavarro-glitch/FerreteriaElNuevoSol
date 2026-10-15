@extends('layouts.app')
@section('title','Categorías · '.$product->name)
@section('content')<h1>Categorías del producto</h1><p class="subtitle">{{ $product->name }}</p>@include('admin.products.sections')
@php($selection = old('category_ids',$selected))
@php($selection = is_array($selection) ? $selection : $selected)
<section class="card"><form method="POST" action="{{ route('admin.products.categories.update',$product) }}">@csrf @method('PUT')
<p>Podés seleccionar varias categorías. Las asociaciones son directas y no agregan automáticamente las categorías padre.</p>
@forelse($tree as $row) @php($category=$row['category'])<label class="check"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id,$selection))><span>{{ str_repeat('— ',$row['depth']).$category->name }} @if(!$category->active)<span class="tag">Inactiva</span>@endif</span></label>@empty<p>No hay categorías. <a href="{{ route('admin.categories.create') }}">Crear una categoría</a>.</p>@endforelse
<div class="actions"><button>Guardar categorías</button><a href="{{ route('admin.categories.index') }}">Administrar categorías</a></div></form></section>@endsection
