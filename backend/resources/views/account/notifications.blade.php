@extends('layouts.storefront')
@php($pageTitle='Notificaciones · El Nuevo Sol')
@php($noindex=true)
@section('content')
@include('account.navigation')
<div class="ens-page-head"><h1>Notificaciones</h1><form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button class="ens-btn ens-btn-outline" type="submit">Marcar todas como leídas</button></form></div>
<div class="ens-notifications">@forelse($notifications as $notification)<article class="ens-block ens-notification {{ $notification->pivot->read_at ? '' : 'ens-unread' }}"><span class="order-badge">{{ $notification->pivot->read_at ? 'Leída' : 'No leída' }}</span><h2>{{ $notification->title }}</h2><p>{{ $notification->message }}</p><time>{{ $notification->published_at->timezone(config('shop.timezone'))->format('d/m/Y H:i') }}</time><div class="ens-notification-actions">
@if($notification->order && $notification->order->user_id === auth()->id())<a href="{{ route('account.orders.show',$notification->order) }}">Ver pedido</a>@endif
@if(!$notification->pivot->read_at)<form method="POST" action="{{ route('account.notifications.read',$notification) }}">@csrf @method('PATCH')<button class="ens-btn ens-btn-outline" type="submit">Marcar como leída</button></form>@endif
</div></article>@empty<div class="ens-empty">No tenés notificaciones.</div>@endforelse</div>
@include('storefront.pagination',['paginator'=>$notifications])
@endsection
