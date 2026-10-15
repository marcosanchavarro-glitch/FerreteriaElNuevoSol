@extends('layouts.app')
@section('title', 'El Nuevo Sol')
@section('content')<section class="card"><h1>Bienvenido a El Nuevo Sol</h1><p>Ingresá a tu cuenta para administrar tus datos.</p><div class="actions">@auth<a class="button" href="{{ route(auth()->user()->can('access-admin') ? 'admin.home' : 'account.edit') }}">Ir a mi área</a>@else<a class="button" href="{{ route('login') }}">Ingresar</a><a href="{{ route('register') }}">Crear cuenta</a>@endauth</div></section>@endsection
