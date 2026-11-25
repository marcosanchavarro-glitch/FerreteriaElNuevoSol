<div class="ens-orders">
@forelse($orders as $order)
<article class="ens-order-card"><div><h3>{{ $order->order_number }}</h3><time>{{ $order->created_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</time>@if(config('shop.order_status_tracking_enabled'))<p class="order-badge order-{{ $order->status->value }}">{{ $order->status->label() }}</p>@endif </div><div><p>Total confirmado: <strong>{{ \App\Support\ProductDisplay::money($order->confirmed_total) }}</strong></p>@if($order->has_items_to_confirm)<p>Incluye productos a confirmar</p>@endif<a class="ens-btn ens-btn-outline" href="{{ route('account.orders.show',$order) }}">Ver pedido</a></div></article>
@empty<div class="ens-empty"><p>Todavía no tenés pedidos.</p><a href="{{ route('store.products') }}">Explorar productos</a></div>@endforelse
</div>
