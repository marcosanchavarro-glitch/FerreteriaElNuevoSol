@extends('layouts.app')
@section('title','Detalle de actividad · El Nuevo Sol')
@section('content')
<a href="{{ route('admin.activity.index') }}">← Historial de actividad</a><h1>Actividad #{{ $log->id }}</h1>
<section class="card"><h2>Información general</h2><dl class="audit-info">
<dt>Responsable</dt><dd>{{ $log->actor_name ?? 'Responsable histórico' }} @if($log->actor_id)(#{{ $log->actor_id }})@endif</dd>
<dt>Rol al realizar la operación</dt><dd>{{ ['admin'=>'Administrador','seller'=>'Vendedor','customer'=>'Cliente'][$log->actor_role] ?? 'Sistema / no disponible' }}</dd>
<dt>Fecha</dt><dd>{{ $log->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i:s') }} (Argentina)</dd>
<dt>Acción</dt><dd>{{ \App\Services\Audit\ActivityLogService::ACTIONS[$log->action] ?? 'Evento histórico' }}</dd>
<dt>Módulo y entidad</dt><dd>{{ \App\Services\Audit\ActivityLogService::MODULES[$log->module] ?? 'Histórico' }} #{{ $log->entity_id ?? '—' }}</dd>
<dt>Resultado</dt><dd>{{ ['success'=>'Exitosa','rejected'=>'Rechazada','unknown'=>'Sin resultado histórico'][$log->result] ?? 'Sin resultado' }}</dd>
<dt>Origen</dt><dd>{{ ['admin'=>'Panel administrativo','system'=>'Sistema','external'=>'Proceso externo','legacy'=>'Histórico'][$log->origin] ?? 'Histórico' }}</dd>
</dl></section>
<h2>Cambios realizados</h2>
@forelse($fields as $field)<section class="card"><h3>{{ \App\Support\ActivityDisplay::field($field) }}</h3><div class="audit-diff"><div><strong>Antes</strong><pre>{{ \App\Support\ActivityDisplay::value($before[$field] ?? null, $field) }}</pre></div><div><strong>Después</strong><pre>{{ \App\Support\ActivityDisplay::value($after[$field] ?? null, $field) }}</pre></div></div></section>@empty<p class="card">Sin cambios de datos. Los intentos rechazados no guardan el contenido enviado; los registros históricos pueden no tener snapshots.</p>@endforelse
@endsection
