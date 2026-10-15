@extends('layouts.app')
@section('title','Variantes · '.$product->name)
@section('content')
<h1>Variantes</h1><p class="subtitle">{{ $product->name }}</p>@include('admin.products.sections')
<div class="toolbar"><p>Precio y stock corresponden a cada presentación.</p>@can('create',[\App\Models\ProductVariant::class,$product])<a class="button" href="{{ route('admin.products.variants.create',$product) }}">Crear variante</a>@endcan</div>
@if($product->product_type === \App\Enums\ProductType::Simple)<div class="notice">Para agregar variantes, cambiá el tipo a Con variantes y dejá vacíos precio y stock del padre.</div>@endif
@if($product->isExternallyManaged())<div class="readonly">Las variantes de este producto provienen del origen externo y son de solo lectura.</div>@endif
<section class="card table-scroll"><table><thead><tr><th>Presentación</th><th>Código</th><th>Precio</th><th>Stock</th><th>Unidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($variants as $variant)<tr><td><strong>{{ $variant->name }}</strong>@if($variant->value && $variant->value !== $variant->name)<br>{{ $variant->value }}@endif</td><td>{{ $variant->sku ?? 'Sin SKU' }}</td><td>{{ \App\Support\ProductDisplay::money($variant->price) }}</td><td>{{ $variant->stock !== null ? str_replace('.',',',$variant->stock) : 'A confirmar' }}</td><td>{{ \App\Support\ProductDisplay::label($variant->effectiveSaleUnit()) }}</td><td>{{ $variant->active ? 'Activa' : 'Inactiva' }}</td><td><a href="{{ route('admin.products.variants.show',[$product,$variant]) }}">Consultar</a> @can('update',$variant)<a href="{{ route('admin.products.variants.edit',[$product,$variant]) }}">Editar</a>@endcan</td></tr>
@empty<tr><td colspan="7" class="empty">Este producto todavía no tiene variantes.</td></tr>@endforelse
</tbody></table></section><div class="pagination">@if($variants->previousPageUrl())<a href="{{ $variants->previousPageUrl() }}">Anterior</a>@endif<span>Página {{ $variants->currentPage() }} de {{ $variants->lastPage() }}</span>@if($variants->nextPageUrl())<a href="{{ $variants->nextPageUrl() }}">Siguiente</a>@endif</div>
@endsection
