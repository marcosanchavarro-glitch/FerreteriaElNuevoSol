<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#263372">
    <title>@yield('heading') · El Nuevo Sol</title>
    <link rel="icon" type="image/png" href="{{ asset('images/branding/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/internal.css') }}?v=brand1">
    <link rel="stylesheet" href="{{ asset('css/branding.css') }}?v=brand1">
</head>
<body>
    <header><div class="wrap"><a class="brand" href="{{ url('/') }}"><img class="ens-logo" src="{{ asset('images/branding/logo-secondary.webp') }}?v=brand1" alt="El Nuevo Sol · Ferretería" width="1400" height="563"></a></div></header>
    <main class="wrap"><section class="card narrow" aria-labelledby="error-heading">
        <p class="admin-eyebrow">@yield('code')</p>
        <h1 id="error-heading">@yield('heading')</h1>
        <p>@yield('message')</p>
        <a class="button" href="{{ url('/') }}">Volver al inicio</a>
    </section></main>
</body>
</html>
