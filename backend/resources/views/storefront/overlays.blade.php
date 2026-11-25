<link rel="stylesheet" href="{{ asset('css/storefront-overlays.css') }}?v=3">
<script src="{{ asset('js/storefront-overlays.js') }}?v=3" defer></script>
@auth
<dialog class="ens-overlay ens-account-dialog" id="ens-account-dialog" aria-labelledby="ens-account-title" data-account-url="{{ route('account.edit') }}">
    <div class="ens-overlay-head"><h2 id="ens-account-title">Mi cuenta</h2><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar mi cuenta">@include('components.store-icon',['name'=>'close'])</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div data-account-content></div>
</dialog>
@endauth
@guest
<dialog class="ens-overlay ens-auth-dialog" id="ens-auth-dialog" aria-labelledby="ens-auth-title" data-login-url="{{ route('login') }}" data-register-url="{{ route('register') }}">
    <div class="ens-overlay-head"><h2 id="ens-auth-title">Tu cuenta</h2><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar ingreso">@include('components.store-icon',['name'=>'close'])</button></div>
    <div class="ens-auth-switch" aria-label="Opciones de acceso"><button type="button" data-auth-mode="login" aria-pressed="true">Ingresar</button><button type="button" data-auth-mode="register" aria-pressed="false">Crear cuenta</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div data-auth-content></div>
    <p class="ens-muted">Podés seguir mirando y armar tu carrito sin crear una cuenta.</p>
</dialog>
@endguest
<dialog class="ens-overlay ens-cart-drawer" id="ens-cart-drawer" aria-labelledby="ens-cart-title" data-cart-url="{{ route('cart.index') }}">
    <div class="ens-overlay-head"><h2 id="ens-cart-title">Tu carrito</h2><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar carrito">@include('components.store-icon',['name'=>'close'])</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div data-cart-content></div>
    <button type="button" class="ens-btn ens-btn-outline" data-overlay-close>Seguir mirando</button>
</dialog>
