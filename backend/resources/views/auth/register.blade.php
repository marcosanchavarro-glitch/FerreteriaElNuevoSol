@extends('layouts.app')
@section('title', 'Crear cuenta · El Nuevo Sol')
@section('content')<section class="card narrow"><h1>Crear cuenta</h1><form method="POST" action="{{ route('register') }}">@csrf
<div class="field"><label for="first_name">Nombre</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="100" autocomplete="given-name"></div>
<div class="field"><label for="last_name">Apellido</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="100" autocomplete="family-name"></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div>
<div class="field"><label for="phone">Teléfono (opcional)</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></div>
<div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-hint"><small class="hint" id="password-hint">Al menos 12 caracteres, con letras y números.</small></div>
<div class="field"><label for="password_confirmation">Repetir contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div><button type="submit">Crear cuenta</button></form></section>@endsection
