@extends('layouts.app')
@section('title','Configuración · El Nuevo Sol')
@section('content')
<h1>Configuración del local</h1>
<p class="subtitle">Completá solamente datos confirmados. Los campos vacíos no se muestran al público.</p>
<form class="card cms-form" method="POST" action="{{ route('admin.settings.update') }}" data-preserve-context data-settings-form>@csrf @method('PUT')
@foreach(['Agenda de contactos'=>['business_name','phone','contact_primary_number','contact_secondary_number','contact_tertiary_number'], 'Destino de pedidos y consultas'=>['whatsapp_orders_contact','whatsapp_number','whatsapp_number_kind','whatsapp_inquiries_contact','whatsapp_inquiries_number','whatsapp_inquiries_kind'], 'Ubicación y retiro'=>['pickup_address','locality','province','postal_code','maps_url','maps_embed_url','pickup_hours']] as $group=>$keys)
<fieldset><legend>{{ $group }}</legend>
@foreach($keys as $field)
<div class="field"><label for="{{ $field }}">{{ $fields[$field] }}</label>
@if(str_ends_with($field,'_contact'))
<select id="{{ $field }}" name="{{ $field }}">@foreach(['custom'=>'Otro número (completar debajo)','primary'=>'Primario','secondary'=>'Secundario','tertiary'=>'Terciario'] as $value=>$label)<option value="{{ $value }}" @selected(old($field,$values[$field]) === $value)>{{ $label }}{{ $value !== 'custom' && $values['contact_'.$value.'_number'] ? ' · +'.$values['contact_'.$value.'_number'] : '' }}</option>@endforeach</select>
<small class="hint">Pedidos y consultas pueden usar el mismo número o destinos diferentes. Los cambios se aplican al guardar.</small>
@elseif(str_ends_with($field,'_kind'))
<select id="{{ $field }}" name="{{ $field }}">@foreach(['pending'=>'Pendiente de confirmar','temporary'=>'Temporal para la primera publicación','business'=>'Número comercial definitivo'] as $value=>$label)<option value="{{ $value }}" @selected(old($field,$values[$field]) === $value)>{{ $label }}</option>@endforeach</select>
<small class="hint">Identificación interna. Revisá y reemplazá el número temporal cuando corresponda.</small>
@else
<input id="{{ $field }}" name="{{ $field }}" type="{{ in_array($field,['maps_url','maps_embed_url']) ? 'url' : (str_contains($field,'number') || $field === 'phone' ? 'tel' : 'text') }}" value="{{ old($field,$values[$field]) }}" maxlength="{{ $field === 'maps_embed_url' ? 4096 : ($field === 'maps_url' ? 2048 : ($field === 'business_name' || in_array($field,['locality','province']) ? 100 : (str_contains($field,'number') ? 15 : ($field === 'phone' ? 40 : ($field === 'postal_code' ? 20 : 255))))) }}" @required($field === 'business_name') @if(str_contains($field,'number')) aria-describedby="{{ $field }}-help" inputmode="numeric" @endif>
@if(str_contains($field,'number'))<small id="{{ $field }}-help" class="hint">Código de país y número, solo dígitos, sin +, espacios ni cero inicial. Vacío deshabilita este contacto. No se envían mensajes automáticamente.</small>@endif
@if($field === 'maps_url')<small class="hint">Pegá el enlace HTTPS de Google Maps del local confirmado.</small>@endif
@if($field === 'maps_embed_url')<small class="hint">Opcional: en Google Maps elegí Compartir → Insertar un mapa → Copiar HTML. Pegá únicamente la dirección entre comillas de src, no el iframe completo. Primero confirmá que el marcador corresponde al comercio. No requiere clave API.</small>@endif
@endif
@error($field)<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
@endforeach
</fieldset>
@endforeach
@php($ordersDestination = app(\App\Services\Content\SiteSettings::class)->contactNumber('orders',$values))
@php($inquiriesDestination = app(\App\Services\Content\SiteSettings::class)->contactNumber('inquiries',$values))
<div class="notice" role="status" data-whatsapp-preview>Pedidos: {{ $ordersDestination ? '+'.$ordersDestination : 'Sin configurar' }} · Consultas: {{ $inquiriesDestination ? '+'.$inquiriesDestination : 'Sin configurar' }}. Los cambios se aplican al guardar.</div>
<button id="settings-save">Guardar configuración</button></form>
<aside class="card" aria-labelledby="location-preview-title"><h2 id="location-preview-title">Revisá la ubicación antes de guardar</h2><p data-location-preview></p><a data-maps-link-preview hidden target="_blank" rel="noopener noreferrer">Probar enlace de Google Maps</a><p class="hint" data-location-preview-status role="status">Completá el enlace para previsualizarlo. Probarlo no guarda cambios.</p></aside>
@endsection
