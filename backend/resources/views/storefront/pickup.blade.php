@php($pickupSettings = app(\App\Services\Content\SiteSettings::class)->all())
<div class="ens-pickup"><strong>Retiro en local</strong>
@if($pickupSettings['pickup_address'])<p>{{ $pickupSettings['pickup_address'] }}</p>@endif
@if($pickupSettings['pickup_hours'])<p>{{ $pickupSettings['pickup_hours'] }}</p>@endif
<p>Coordinaremos la disponibilidad y el retiro al confirmar tu pedido.</p></div>
