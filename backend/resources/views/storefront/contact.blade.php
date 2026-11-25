@extends('layouts.storefront')
@php($pageTitle='Ubicación y contacto · El Nuevo Sol')
@php($contactSettings=app(\App\Services\Content\SiteSettings::class)->all())
@php($mapEmbed=app(\App\Services\Content\SiteSettings::class)->mapEmbedUrl($contactSettings))
@php($contactWhatsapp=app(\App\Services\Content\SiteSettings::class)->inquiryUrl($contactSettings))
@section('content')
<div class="ens-page-head"><div><span class="ens-kicker">Estamos para ayudarte</span><h1>Ubicación y contacto</h1></div></div>
<div class="ens-contact-grid">
<section class="ens-block ens-contact-details" aria-labelledby="contact-business"><h2 id="contact-business">{{ $contactSettings['business_name'] }}</h2>
@if($contactSettings['pickup_address'] || $contactSettings['locality'] || $contactSettings['province'] || $contactSettings['postal_code'])
<address>@foreach(['pickup_address','locality','province'] as $field)@if($contactSettings[$field])<span>{{ $contactSettings[$field] }}</span>@endif @endforeach @if($contactSettings['postal_code'])<span>Código postal: {{ $contactSettings['postal_code'] }}</span>@endif</address>
@endif
@if(!$contactSettings['pickup_address'] || !$contactSettings['locality'] || !$contactSettings['province'])<p class="ens-notice">La información de ubicación está pendiente de completar. Consultanos antes de acercarte.</p>@endif
@if($contactSettings['pickup_hours'])<h3>Horarios de atención</h3><p class="ens-contact-hours">{{ $contactSettings['pickup_hours'] }}</p>@endif
@if($contactSettings['phone'])<h3>Teléfono</h3><p>{{ $contactSettings['phone'] }}</p>@endif
<div class="ens-contact-actions">
@if($contactSettings['maps_url'])<a class="ens-btn ens-btn-primary" data-contact-directions href="{{ $contactSettings['maps_url'] }}" target="_blank" rel="noopener noreferrer">Cómo llegar</a>@endif
@if($contactWhatsapp)<a class="ens-btn ens-btn-outline" href="{{ $contactWhatsapp }}" target="_blank" rel="noopener noreferrer">Consultar por WhatsApp</a>@endif
</div>
@if(!$contactWhatsapp)<p class="ens-muted">{{ config('demo.enabled') ? 'El contacto por WhatsApp está deshabilitado en la demostración.' : 'El contacto por WhatsApp está pendiente de configuración.' }}</p>@endif
</section>
<section class="ens-block ens-contact-map" aria-labelledby="contact-map-title"><h2 id="contact-map-title">Encontranos</h2>
@if($mapEmbed)
<p>El mapa de Google Maps se carga solo cuando lo solicitás.</p>
<div class="ens-map-shell" data-map-embed="{{ $mapEmbed }}"><button class="ens-btn ens-btn-outline" type="button" data-load-map>Cargar mapa de Google Maps</button></div>
<p role="status" data-map-status>También podés usar «Cómo llegar» para abrir el mapa directamente.</p>
<noscript><p>Para ver el mapa integrado necesitás JavaScript. Usá «Cómo llegar» para abrirlo en Google Maps.</p></noscript>
@else
<p>El mapa integrado todavía no está configurado.</p>
@endif
@if($contactSettings['maps_url'])<p><a href="{{ $contactSettings['maps_url'] }}" target="_blank" rel="noopener noreferrer">Abrir ubicación en Google Maps</a></p>@else<p class="ens-muted">El enlace de ubicación está pendiente de confirmación.</p>@endif
</section></div>
@endsection