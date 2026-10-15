@extends('layouts.app')
@section('title','Historial de actividad · El Nuevo Sol')
@section('content')
<h1>Historial de actividad</h1><p class="subtitle">Cambios administrativos y responsables. Horarios de Argentina. Registros de solo lectura.</p>
<form method="GET" class="card audit-filters">
<div class="field"><label for="q">Buscar responsable o entidad</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"></div>
<div class="field"><label for="actor">ID del responsable</label><input type="number" min="1" id="actor" name="actor" value="{{ $filters['actor'] ?? '' }}"></div>
@foreach(['module'=>['Módulo',\App\Services\Audit\ActivityLogService::MODULES],'action'=>['Acción',\App\Services\Audit\ActivityLogService::ACTIONS],'result'=>['Resultado',['success'=>'Exitosa','rejected'=>'Rechazada','unknown'=>'Histórico sin resultado']]] as $key=>[$label,$options])
<div class="field"><label for="{{ $key }}">{{ $label }}</label><select id="{{ $key }}" name="{{ $key }}"><option value="">Todos</option>@foreach($options as $value=>$text)<option value="{{ $value }}" @selected(($filters[$key] ?? '') === $value)>{{ $text }}</option>@endforeach</select></div>
@endforeach
<div class="field"><label for="entity">ID de entidad</label><input id="entity" name="entity" value="{{ $filters['entity'] ?? '' }}" maxlength="64"></div>
@foreach(['from'=>'Desde','to'=>'Hasta'] as $key=>$label)<div class="field"><label for="{{ $key }}">{{ $label }}</label><input type="date" id="{{ $key }}" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"></div>@endforeach
<div class="actions"><button>Filtrar</button><a href="{{ route('admin.activity.index') }}">Limpiar filtros</a></div>
</form>
<p>{{ $logs->total() }} eventos</p>
<div class="audit-list">
@forelse($logs as $log)<article class="card audit-event">
<div><time>{{ $log->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i:s') }}</time><h2>{{ \App\Services\Audit\ActivityLogService::ACTIONS[$log->action] ?? 'Evento histórico' }}</h2><p>{{ $log->actor_name ?? 'Responsable histórico' }} @if($log->actor_id) · #{{ $log->actor_id }}@endif</p></div>
<div><p>{{ \App\Services\Audit\ActivityLogService::MODULES[$log->module] ?? 'Histórico' }} · Entidad #{{ $log->entity_id ?? '—' }}</p><span class="badge">{{ ['success'=>'Exitosa','rejected'=>'Rechazada','unknown'=>'Sin resultado histórico'][$log->result] ?? 'Sin resultado' }}</span></div>
<a class="button secondary" href="{{ route('admin.activity.show',$log) }}">Ver detalle #{{ $log->id }}</a>
</article>@empty<div class="card">No hay eventos para estos filtros.</div>@endforelse
</div>
@include('storefront.pagination',['paginator'=>$logs])
@endsection
