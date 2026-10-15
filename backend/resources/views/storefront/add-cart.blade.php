@if($offer)
<form method="POST" action="{{ route('cart.store') }}" class="ens-add-cart">@csrf
<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="product_variant_id" value="{{ $selected?->id }}" data-cart-variant>
<label for="product-quantity">Cantidad</label><div class="ens-quantity" data-quantity><button type="button" data-delta="-1" aria-label="Disminuir cantidad">−</button><input id="product-quantity" name="quantity" value="1" inputmode="decimal" data-step="{{ $offer['step'] }}" required><button type="button" data-delta="1" aria-label="Aumentar cantidad">+</button></div><small>Decimales en metros, peso y volumen; hasta tres cifras.</small>
<button class="ens-btn ens-btn-primary" type="submit" data-cart-add @disabled(!$offer['can_add'])>{{ $offer['can_add'] ? 'Agregar al carrito' : 'Sin stock' }}</button>
</form>
@endif
