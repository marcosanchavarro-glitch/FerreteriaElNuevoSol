<link rel="stylesheet" href="{{ asset('css/storefront-overlays.css') }}?v=final6">
<script src="{{ asset('js/storefront-overlays.js') }}?v=final6" defer></script>
@auth
<dialog class="ens-overlay ens-account-dialog" id="ens-account-dialog" aria-labelledby="ens-account-title" data-account-url="{{ route('account.edit') }}">
    <div class="ens-overlay-head"><h2 id="ens-account-title">Mi cuenta</h2><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar mi cuenta">@include('components.store-icon',['name'=>'close'])</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div data-account-content></div>
</dialog>
@endauth
@guest
<dialog class="ens-overlay ens-auth-dialog" id="ens-auth-dialog" aria-labelledby="ens-auth-title" data-login-url="{{ route('login') }}" data-register-url="{{ route('register') }}">
    <div class="ens-overlay-head ens-auth-heading"><div class="ens-auth-brand"><img src="{{ asset('images/branding/isotipo.svg') }}" width="48" height="40" alt=""><span>El Nuevo Sol<small>FERRETERÍA</small></span></div><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar ingreso">@include('components.store-icon',['name'=>'close'])</button></div>
    <h2 id="ens-auth-title">Ingresá a tu cuenta</h2>
    <p class="ens-auth-intro">Consultá tus pedidos y tené tus datos a mano.</p>
    <div class="ens-auth-switch" aria-label="Opciones de acceso"><button type="button" data-auth-mode="login" aria-pressed="true">Ingresar</button><button type="button" data-auth-mode="register" aria-pressed="false">Crear cuenta</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div class="ens-auth-scroll"><div data-auth-content></div></div>
    <p class="ens-auth-note">¿Solo querés mirar? Podés comprar sin crear una cuenta.</p>
</dialog>
@endguest
<dialog class="ens-overlay ens-cart-drawer" id="ens-cart-drawer" aria-labelledby="ens-cart-title" data-cart-url="{{ route('cart.index') }}">
    <div class="ens-overlay-head"><h2 id="ens-cart-title">Tu carrito</h2><button type="button" class="ens-overlay-close" data-overlay-close aria-label="Cerrar carrito">@include('components.store-icon',['name'=>'close'])</button></div>
    <p class="ens-overlay-status" role="status" data-overlay-status></p>
    <div data-cart-content></div>
    <button type="button" class="ens-btn ens-btn-outline" data-overlay-close>Seguir mirando</button>
</dialog>
