<div class="order-snapshots">
@foreach($order->items as $item)
<article class="order-snapshot"><div><h3>{{ $item->product_name }}</h3>@if($item->variant_name)<p>{{ $item->variant_name }}</p>@endif<p>SKU: {{ $item->sku ?? 'Sin código' }}</p></div><div><p>{{ \App\Support\ProductDisplay::quantity($item->quantity) }} {{ \App\Support\ProductDisplay::label($item->sale_unit) }}</p><p>Precio unitario: {{ \App\Support\ProductDisplay::money($item->unit_price) }}</p><strong>Subtotal: {{ \App\Support\ProductDisplay::money($item->subtotal) }}</strong>@if($item->requires_confirmation)<p class="order-badge">A confirmar</p>@endif</div></article>
@endforeach
</div><div class="order-total"><h2>Total confirmado: {{ \App\Support\ProductDisplay::money($order->confirmed_total) }}</h2><p>Productos a confirmar: {{ $order->items->where('requires_confirmation',true)->count() }}</p>@if($order->has_items_to_confirm)<p>Los productos a confirmar no están incluidos en este importe.</p>@endif</div>
