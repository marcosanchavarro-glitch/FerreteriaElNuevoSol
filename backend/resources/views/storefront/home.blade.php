@extends('layouts.storefront')
@php($pageTitle='El Nuevo Sol · Herramientas y materiales')
@section('content')
@forelse($sections as $section)
@php($kind = $section->type->value)
@php($limit = max(1,min(24,(int)($section->settings['max_items'] ?? 6))))
@if($kind === 'hero')
<section class="ens-hero ens-home-hero"><div class="ens-hero-copy">@if($section->subtitle)<span class="ens-eyebrow">{{ $section->subtitle }}</span>@endif<h1>{{ $section->title }}</h1><p class="cms-copy">{{ $section->content }}</p>@include('storefront.content-button',['content'=>$section,'buttonClass'=>'ens-btn-primary'])</div><div class="ens-hero-art">@if($section->image_path && $publicImages->available($section->image_path,'sections/'.$section->id))<img class="cms-hero-image" src="{{ route('store.images.content',['kind'=>'sections','id'=>$section->id]) }}" alt="{{ $section->title }}">@else<img class="ens-hero-logo" src="{{ asset('images/branding/logo-primary.png') }}" alt="El Nuevo Sol · Ferretería" width="646" height="224">@endif</div></section>
@elseif($kind === 'featured_products')
<section class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-products">@forelse($products->take($limit) as $item)@include('storefront.card')@empty<p>Pronto vas a encontrar novedades.</p>@endforelse</div>@include('storefront.content-button',['content'=>$section])</section>
@elseif($kind === 'featured_categories')
<section id="categorias" class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-quick">@forelse($featuredCategories->take($limit) as $category)<a class="ens-quick-card" href="{{ route('store.category',$category->slug) }}">@if($category->image_path)<img class="ens-category-thumbnail" src="{{ $publicImages->category($category) }}" alt="" loading="lazy">@else<span class="ens-quick-icon" aria-hidden="true">↗</span>@endif<strong>{{ $category->name }}</strong><span>Explorar categoría</span></a>@empty<p>Estamos preparando nuestras categorías destacadas.</p>@endforelse</div>@include('storefront.content-button',['content'=>$section])</section>
@elseif($kind === 'banner')
@if($banners->isNotEmpty())<section class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-split">@foreach($banners as $banner)@include('storefront.content-block',['content'=>$banner,'mediaKind'=>'banners','promotion'=>false])@endforeach</div>@include('storefront.content-button',['content'=>$section])</section>@endif
@else
<section class="ens-section">@include('storefront.content-block',['content'=>$section,'mediaKind'=>'sections','promotion'=>$kind === 'promotion'])</section>
@endif
@empty<section class="ens-block ens-section"><h1>El Nuevo Sol · Ferretería</h1><p>Encontrá herramientas y materiales para tus proyectos.</p><a class="ens-btn ens-btn-primary" href="{{ route('store.products') }}">Ver catálogo</a></section>@endforelse
@endsection