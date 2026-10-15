<!doctype html>

<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="referrer" content="same-origin"><title>@yield('title', 'El Nuevo Sol')</title><link rel="stylesheet" href="{{ asset('css/internal.css') }}"><link rel="stylesheet" href="{{ asset('css/orders.css') }}"><link rel="stylesheet" href="{{ asset('css/branding.css') }}?v=1"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"><script src="{{ asset('js/admin.js') }}" defer></script></head>

<body>@include('components.demo-notice')<header><div class="wrap"><a class="brand" href="{{ route('home') }}"><img class="ens-logo" src="{{ asset('images/branding/logo-primary.png') }}" alt="El Nuevo Sol · Ferretería" width="646" height="224"></a><nav aria-label="Navegación principal">

@auth

 @can('access-admin')<a href="{{ route('admin.home') }}">Administración</a><a href="{{ route('home') }}">Ver sitio</a>@endcan

 <a href="{{ route(auth()->user()->can('access-admin') ? 'admin.profile.edit' : 'account.edit') }}">Mi cuenta</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="link-button" type="submit">Cerrar sesión</button></form>

@else<a href="{{ route('login') }}">Ingresar</a><a href="{{ route('register') }}">Crear cuenta</a>@endauth

</nav></div></header>@can('access-admin')<div class="admin-shell"><details class="admin-sidebar" open><summary>Menú administrativo</summary>@include('admin.navigation')</details>@endcan<main class="wrap">

@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif

@if($errors->any())<div class="errors" role="alert"><strong>Revisá los datos del formulario.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@yield('content')</main>@can('access-admin')</div>@endcan</body></html>



