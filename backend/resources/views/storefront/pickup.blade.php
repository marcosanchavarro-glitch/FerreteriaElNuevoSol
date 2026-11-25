@php($pickupSettings = app(\App\Services\Content\SiteSettings::class)->all())
<div class="ens-pickup"><strong>Retiro en local</strong>
@if($pickupSettings['pickup_address'])<p>{{ $pickupSettings['pickup_address'] }}</p>@endif
@if($pickupSettings['locality'] || $pickupSettings['province'])<p>{{ implode(', ',array_filter([$pickupSettings['locality'],$pickupSettings['province']])) }}</p>@endif
@if($pickupSettings['postal_code'])<p>Código postal: {{ $pickupSettings['postal_code'] }}</p>@endif
@if($pickupSettings['pickup_hours'])<p>{{ $pickupSettings['pickup_hours'] }}</p>@endif
@if($pickupSettings['maps_url'])<a class="ens-btn ens-btn-outline" href="{{ $pickupSettings['maps_url'] }}" target="_blank" rel="noopener noreferrer">Cómo llegar</a>@endif
<p>Coordinaremos la disponibilidad y el retiro al confirmar tu pedido.</p></div>
