<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access-admin');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->viewAny($user) || ($user->can('access-account') && $order->user_id === $user->id);
    }

    public function update(User $user, Order $order): bool
    {
        return (bool) config('shop.order_status_tracking_enabled') && $this->viewAny($user);
    }
}
