@extends('layouts.storefront')
@php($pageTitle='Mi cuenta · El Nuevo Sol')
@php($noindex=true)
@section('title', 'Mi cuenta · El Nuevo Sol')
@section('content') @include('account.navigation') <h1>Mi cuenta</h1><section class="ens-account-welcome"><span class="ens-kicker">Hola, {{ $user->first_name }}</span><h2>Tus pedidos recientes</h2>@include('account.orders.list', ['orders'=>$recentOrders])</section><h2>Mi perfil</h2><p class="subtitle">Tus datos personales y la seguridad de tu cuenta.</p><div class="ens-profile-grid"><section class="ens-block ens-profile-card"><h2>Mis datos</h2><form method="POST" action="{{ route('account.update') }}">@csrf @method('PATCH')
<div class="ens-field"><label for="first_name">Nombre</label><input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="100" autocomplete="given-name"></div>
<div class="ens-field"><label for="last_name">Apellido</label><input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="100" autocomplete="family-name"></div>
<div class="ens-field"><label for="phone">Teléfono</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="40" autocomplete="tel"></div>
<div class="ens-field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></div>
<div class="ens-field"><label for="profile_current_password">Contraseña actual, si cambiás el email</label><input id="profile_current_password" name="current_password" type="password" autocomplete="current-password"></div><button class="ens-btn ens-btn-primary" type="submit">Guardar datos</button></form></section>
<section class="ens-block ens-profile-card"><h2>Cambiar contraseña</h2><form method="POST" action="{{ route('account.password') }}">@csrf @method('PUT')
<div class="ens-field"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
<div class="ens-field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><small class="hint">Al menos 12 caracteres, con letras y números.</small></div>
<div class="ens-field"><label for="password_confirmation">Repetir nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div><button class="ens-btn ens-btn-primary" type="submit">Actualizar contraseña</button></form></section></div>@endsection


