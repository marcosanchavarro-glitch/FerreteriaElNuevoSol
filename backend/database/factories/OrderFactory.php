<?php

namespace Database\Factories;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return ['order_number' => 'DEMO-'.fake()->unique()->uuid(), 'user_id' => UserFactory::new(), 'customer_first_name' => fake()->firstName(), 'customer_last_name' => fake()->lastName(), 'customer_phone' => '0000000000', 'customer_email' => null, 'status' => OrderStatus::Received, 'subtotal' => 0, 'confirmed_total' => 0, 'has_items_to_confirm' => false, 'fulfillment_method' => FulfillmentMethod::Pickup];
    }

    public function guest(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }
}
