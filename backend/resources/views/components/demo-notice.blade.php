@if(config('demo.enabled'))
<aside class="demo-notice" role="note">{{ config('demo.access_required') ? 'Demostración privada' : 'Sitio de demostración' }} · Los productos y pedidos son de prueba. No se realizan compras ni envíos.</aside>
@endif
