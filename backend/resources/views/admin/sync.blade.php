@extends('layouts.app')
@section('title', 'Sincronización comercial · El Nuevo Sol')
@section('content')
<h1>Sincronización comercial por SKU</h1>
<p>Importación manual de prueba. No está conectada con Punto de Venta.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.sync.preview') }}">
    @csrf
    <label for="file">Archivo JSON de prueba</label>
    <input id="file" type="file" name="file" accept=".json,application/json" required>
    <p>Máximo 256 KB y 100 registros. Precio Lista 1 en centavos enteros; stock como texto decimal con punto. Los campos omitidos se conservan. Consultá docs/integracion-punto-de-venta.md para el contrato.</p>
    <button type="submit">Previsualizar sin modificar productos</button>
</form>
@if($run)
@php($details = $run->error_details ?? [])
<h2>{{ ($details['mode'] ?? '') === 'preview' ? 'Vista previa' : 'Resultado' }} #{{ $run->id }}</h2>
<p>{{ $run->started_at }} · Origen: JSON normalizado de prueba · Estado: {{ $run->status->value }}</p>
<p>Procesados: {{ $run->processed_count }} · Creados: {{ $run->created_count }} · Actualizados: {{ $run->updated_count }} · Omitidos: {{ $details['skipped_count'] ?? '—' }} · Errores/conflictos: {{ $run->error_count }}</p>
@if(isset($details['message']))<p role="alert">{{ $details['message'] }}</p>@endif
@if(($details['mode'] ?? '') === 'preview')
@php($counts = array_count_values(array_column($details['rows'], 'action')))
<p>Previstos: {{ $counts['create'] ?? 0 }} altas · {{ $counts['update'] ?? 0 }} actualizaciones · {{ $counts['unchanged'] ?? 0 }} sin cambios · {{ ($counts['invalid'] ?? 0) + ($counts['conflict'] ?? 0) }} omitidos.</p>
<p>Esta simulación no modifica el catálogo. Las filas inválidas o en conflicto se omitirán. Altas: productos en borrador y variantes inactivas. Confirmar también marca los registros afectados como gestionados externamente.</p>
@endif
<div class="table-wrap"><table><thead><tr><th>Fila / SKU</th><th>Tipo</th><th>Acción</th><th>Antes → Después</th><th>Detalle</th></tr></thead><tbody>
@foreach($details['rows'] ?? [] as $row)
<tr><td>{{ $row['line'] }} · {{ $row['sku'] ?? 'Sin SKU válido' }}</td><td>{{ $row['kind'] ?? '—' }}</td><td>{{ ['create'=>'Crear','update'=>'Actualizar','unchanged'=>'Sin cambios','conflict'=>'Conflicto','invalid'=>'Inválido'][$row['action']] }}</td><td>@foreach($row['after'] ?? [] as $field => $value)<div>{{ $field === 'price' ? 'Precio (centavos)' : 'Stock' }}: {{ $row['before'][$field] ?? 'Sin dato' }} → {{ $value }}</div>@endforeach</td><td>{{ $row['reason'] }}</td></tr>
@endforeach
</tbody></table></div>
@if(($details['mode'] ?? '') === 'preview' && ($details['actor_id'] ?? null) === auth()->id() && !isset($details['applied_run_id']) && ($details['expires_at'] ?? 0) >= now()->timestamp)
<form method="POST" action="{{ route('admin.sync.apply', $run) }}">@csrf
<label><input type="checkbox" name="confirm" value="1" required> Confirmo los cambios comerciales válidos de esta vista previa.</label>
<button type="submit">Confirmar importación</button></form>
@endif
@endif
<h2>Últimas ejecuciones</h2><ul>@foreach($history as $item)<li><a href="{{ route('admin.sync.index', ['run'=>$item->id]) }}">#{{ $item->id }} · {{ $item->started_at }} · {{ ($item->error_details['mode'] ?? '') === 'preview' ? 'Vista previa' : 'Aplicación' }} · {{ $item->status->value }}</a></li>@endforeach</ul>
@endsection
