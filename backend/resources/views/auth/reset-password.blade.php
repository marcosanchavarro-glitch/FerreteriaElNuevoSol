@extends('layouts.app')
@section('title', 'Restablecer contraseña · El Nuevo Sol')
@section('content')<section class="card narrow"><h1>Nueva contraseña</h1><form method="POST" action="{{ route('password.store') }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email"></div>
<div class="field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><small class="hint">Al menos 12 caracteres, con letras y números.</small></div>
<div class="field"><label for="password_confirmation">Repetir nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div><button type="submit">Guardar contraseña</button></form></section>@endsection
