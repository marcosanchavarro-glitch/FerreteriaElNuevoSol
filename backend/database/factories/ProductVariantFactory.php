<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return ['product_id' => ProductFactory::new()->variable(), 'name' => '4 L', 'value' => '4 L', 'price' => 245000, 'stock' => '8.000', 'active' => true];
    }
}
