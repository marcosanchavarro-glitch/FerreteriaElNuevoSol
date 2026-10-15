<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;

class OrderNotifications
{
    /** Called inside the same transaction as the corresponding order/history write. */
    public function record(Order $order, ?User $actor = null): void
    {
        if ($order->user_id === null) {
            return;
        }
        $number = '#'.$order->order_number;
        $message = match ($order->status) {
            OrderStatus::Received => 'Recibimos tu pedido '.$number.'.',
            OrderStatus::Preparing => 'Estamos preparando tu pedido '.$number.'.',
            OrderStatus::ReadyForPickup => 'Tu pedido '.$number.' está listo para retirar.',
            OrderStatus::PickedUp => 'El pedido '.$number.' fue marcado como retirado.',
            OrderStatus::Cancelled => 'El pedido '.$number.' fue cancelado.',
        };
        $notification = Notification::firstOrCreate(
            ['order_id' => $order->id, 'type' => 'order.'.$order->status->value],
            ['title' => 'Pedido '.$order->status->label(), 'message' => $message,
                'created_by' => $actor?->id, 'active' => true, 'published_at' => now()]
        );
        $notification->users()->syncWithoutDetaching([$order->user_id]);
    }
}
