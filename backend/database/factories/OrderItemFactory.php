<?php

namespace Database\Factories;

use App\Enums\SaleUnit;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return ['order_id' => OrderFactory::new(), 'product_id' => ProductFactory::new(), 'product_name' => 'Producto histórico de prueba', 'sku' => 'SNAP-01', 'sale_unit' => SaleUnit::Unit, 'quantity' => '2.000', 'unit_price' => 125050, 'subtotal' => 250100, 'requires_confirmation' => false];
    }
}
