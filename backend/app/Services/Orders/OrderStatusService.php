<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function __construct(private OrderNotifications $notifications) {}

    public function change(Order $order, User $actor, OrderStatus $expected, OrderStatus $next): Order
    {
        Gate::forUser($actor)->authorize('update', $order);

        return DB::transaction(function () use ($order, $actor, $expected, $next): Order {
            // SQLite acquires its write lock before reading the authoritative state.
            Order::whereKey($order->id)->update(['updated_at' => DB::raw('updated_at')]);
            $current = Order::findOrFail($order->id);
            $actor = $actor->fresh() ?? abort(403);
            Gate::forUser($actor)->authorize('update', $current);
            if ($current->status !== $expected) {
                throw ValidationException::withMessages(['status' => 'El pedido cambió mientras lo revisabas. Actualizá la página antes de continuar.']);
            }
            if (! in_array($next, $current->status->nextStates(), true)) {
                throw ValidationException::withMessages(['status' => 'No se permite ese cambio de estado.']);
            }
            $before = app(ActivityLogService::class)->snapshot($current);
            $previous = $current->status;
            $current->update(['status' => $next]);
            $current->statusHistory()->create(['previous_status' => $previous, 'new_status' => $next, 'changed_by' => $actor->id]);
            $this->notifications->record($current, $actor);
            app(ActivityLogService::class)->changed($current, $before, 'status', $actor);

            return $current;
        }, 3);
    }
}
