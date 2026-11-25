@extends('layouts.storefront')
@php($pageTitle='El Nuevo Sol · Herramientas y materiales')
@section('content')
@php($heroCount=0)
@forelse($sections as $section)
@php($kind = $section->type->value)
@php($limit = max(1,min(24,(int)($section->settings['max_items'] ?? 6))))
@if($kind === 'hero')
@php($heroCount++)
<section class="ens-hero ens-home-hero" aria-labelledby="hero-title-{{ $loop->index }}">
<div class="ens-hero-copy">
@if($section->subtitle)<span class="ens-eyebrow">{{ $section->subtitle }}</span>@endif
@if($heroCount === 1)<h1 id="hero-title-{{ $loop->index }}" class="ens-hero-title">{{ $section->title }}</h1>@else<h2 id="hero-title-{{ $loop->index }}" class="ens-hero-title">{{ $section->title }}</h2>@endif
@if($section->content)<p class="cms-copy">{{ $section->content }}</p>@endif
@include('storefront.content-button',['content'=>$section,'buttonClass'=>'ens-btn-primary'])
</div>
<div class="ens-hero-art">
@if(isset($previewImages['sections/'.$section->id]))
<img class="cms-hero-image" src="{{ $previewImages['sections/'.$section->id] }}" alt="{{ $section->title }}" decoding="async">
@elseif($section->image_path && $publicImages->available($section->image_path,'sections/'.$section->id))
<img class="cms-hero-image" src="{{ route('store.images.content',['kind'=>'sections','id'=>$section->id]) }}" alt="{{ $section->title }}" loading="{{ $heroCount === 1 ? 'eager' : 'lazy' }}" fetchpriority="{{ $heroCount === 1 ? 'high' : 'auto' }}" decoding="async">
@else
<img class="ens-hero-logo" src="{{ asset('images/branding/logo-secondary.webp') }}?v=brand1" alt="El Nuevo Sol · Ferretería" width="1400" height="563" decoding="async">
@endif
</div></section>
@elseif($kind === 'featured_products')
<section class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-products">@forelse($products->take($limit) as $item)@include('storefront.card')@empty<p>Pronto vas a encontrar novedades.</p>@endforelse</div>@include('storefront.content-button',['content'=>$section])</section>
@elseif($kind === 'featured_categories')
<section id="categorias" class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-quick">@forelse($featuredCategories->take($limit) as $category)<a class="ens-quick-card" href="{{ route('store.category',$category->slug) }}">@if($category->image_path || $category->products->first()?->images->first())<img class="ens-category-thumbnail" src="{{ $category->image_path ? $publicImages->category($category) : $publicImages->product($category->products->first()?->images->first()) }}" alt="" loading="lazy">@else<span class="ens-quick-icon" aria-hidden="true">↗</span>@endif<strong>{{ $category->name }}</strong><span>Explorar categoría</span></a>@empty<p>Estamos preparando nuestras categorías destacadas.</p>@endforelse</div>@include('storefront.content-button',['content'=>$section])</section>
@elseif($kind === 'banner')
@if($banners->isNotEmpty())<section class="ens-section"><div class="ens-page-head"><h2>{{ $section->title }}</h2></div>@if($section->subtitle)<p>{{ $section->subtitle }}</p>@endif<div class="ens-split">@foreach($banners as $banner)@include('storefront.content-block',['content'=>$banner,'mediaKind'=>'banners','promotion'=>false])@endforeach</div>@include('storefront.content-button',['content'=>$section])</section>@endif
@else
<section class="ens-section">@include('storefront.content-block',['content'=>$section,'mediaKind'=>'sections','promotion'=>$kind === 'promotion'])</section>
@endif
@empty<section class="ens-block ens-section"><h1>El Nuevo Sol · Ferretería</h1><p>Encontrá herramientas y materiales para tus proyectos.</p><a class="ens-btn ens-btn-primary" href="{{ route('store.products') }}">Ver catálogo</a></section>@endforelse
@endsection
