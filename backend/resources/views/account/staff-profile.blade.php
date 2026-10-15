@extends('layouts.app')
@section('title', 'Mi cuenta · El Nuevo Sol')
@section('content')<h1>Mi cuenta</h1><p class="subtitle">Tus datos personales y la seguridad de tu cuenta.</p><div class="grid"><section class="card"><h2>Mis datos</h2><form method="POST" action="{{ route('admin.profile.update') }}">@csrf @method('PATCH')
<div class="field"><label for="first_name">Nombre</label><input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="100" autocomplete="given-name"></div>
<div class="field"><label for="last_name">Apellido</label><input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="100" autocomplete="family-name"></div>
<div class="field"><label for="phone">Teléfono</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="40" autocomplete="tel"></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></div>
<div class="field"><label for="profile_current_password">Contraseña actual, si cambiás el email</label><input id="profile_current_password" name="current_password" type="password" autocomplete="current-password"></div><button type="submit">Guardar datos</button></form></section>
<section class="card"><h2>Cambiar contraseña</h2><form method="POST" action="{{ route('admin.profile.password') }}">@csrf @method('PUT')
<div class="field"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
<div class="field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><small class="hint">Al menos 12 caracteres, con letras y números.</small></div>
<div class="field"><label for="password_confirmation">Repetir nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div><button type="submit">Actualizar contraseña</button></form></section></div>@endsection

