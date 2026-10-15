<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderStatusHistory> */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return ['order_id' => OrderFactory::new(), 'previous_status' => null, 'new_status' => OrderStatus::Received, 'changed_by' => null];
    }
}
