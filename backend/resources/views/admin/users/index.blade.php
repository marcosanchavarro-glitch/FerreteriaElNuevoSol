@extends('layouts.app')
@section('title','Usuarios · El Nuevo Sol')
@section('content')
<h1>Usuarios y permisos</h1><p class="subtitle">Asigná el acceso de las cuentas existentes. No se modifican sus contraseñas ni sus datos personales.</p>
<section class="card"><h2>Qué permite cada rol</h2><p><strong>Administrador:</strong> gestión completa, configuración, usuarios e integración comercial.</p><p><strong>Vendedor:</strong> productos, categorías, contenido y consulta de pedidos; no administra usuarios ni configuración comercial.</p><p><strong>Cliente:</strong> catálogo, carrito y sus propios pedidos.</p></section>
<form method="GET" action="{{ route('admin.users.index') }}" class="card"><label for="user-search">Buscar por nombre o email</label><div class="actions"><input id="user-search" name="q" value="{{ $search }}" maxlength="100"><button type="submit">Buscar usuario</button><a href="{{ route('admin.users.index') }}">Limpiar</a></div></form>
<section class="card"><p>{{ $users->total() }} usuarios</p><div class="table-scroll"><table><thead><tr><th>Usuario</th><th>Acceso</th><th>Rol</th></tr></thead><tbody>
@forelse($users as $account)<tr><td>{{ $account->first_name }} {{ $account->last_name }}<br><small>{{ $account->email }}</small></td><td>{{ $account->active ? 'Cuenta activa' : 'Cuenta inactiva' }}</td><td>
@if($account->id === auth()->id())<strong>Administrador · Tu cuenta</strong><p class="hint">No podés cambiar tu propio rol.</p>
@else<form method="POST" action="{{ route('admin.users.update',$account) }}" data-confirm="¿Confirmás cambiar los permisos de esta cuenta?" data-preserve-context>@csrf @method('PATCH')<input type="hidden" name="expected_role" value="{{ $account->role->value }}"><label for="role-{{ $account->id }}">Rol de {{ $account->first_name }}</label><select id="role-{{ $account->id }}" name="role">@foreach(['admin'=>'Administrador','seller'=>'Vendedor','customer'=>'Cliente'] as $value=>$label)<option value="{{ $value }}" @selected($account->role->value === $value)>{{ $label }}</option>@endforeach</select><button type="submit">Guardar rol</button></form>@endif
</td></tr>@empty<tr><td colspan="3">No hay usuarios que coincidan con la búsqueda.</td></tr>@endforelse
</tbody></table></div>@include('storefront.pagination',['paginator'=>$users])</section>
@endsection
