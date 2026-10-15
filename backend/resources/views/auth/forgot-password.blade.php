@extends('layouts.app')
@section('title', 'Recuperar contraseña · El Nuevo Sol')
@section('content')<section class="card narrow"><h1>Recuperar contraseña</h1><p>Ingresá el email de tu cuenta para solicitar un enlace de restablecimiento.</p>
@if(app()->environment('local') && config('mail.default') === 'array')<p class="notice">El envío de correos todavía no está habilitado en este entorno.</p>@endif
<form method="POST" action="{{ route('password.email') }}">@csrf<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div><button type="submit">Solicitar enlace</button></form><p><a href="{{ route('login') }}">Volver a ingresar</a></p></section>@endsection
