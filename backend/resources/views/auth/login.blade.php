@extends('layouts.app')
@section('title', 'Ingresar · El Nuevo Sol')
@section('content')<section class="card narrow"><h1>Ingresar</h1><p class="subtitle">Accedé a tu cuenta.</p><form method="POST" action="{{ route('login') }}">@csrf
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus></div>
<div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
<div class="field"><label class="check"><input name="remember" type="checkbox" value="1" @checked(old('remember'))>Recordar sesión</label></div>
<button type="submit">Ingresar</button></form><p><a href="{{ route('password.request') }}">Olvidé mi contraseña</a></p><a href="{{ route('register') }}">Crear una cuenta</a></section>@endsection
