<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true), 'slug' => fake()->unique()->slug().'-'.fake()->uuid(),
            'product_type' => ProductType::Simple, 'sale_unit' => SaleUnit::Unit,
            'purchase_mode' => PurchaseMode::Calculated, 'price' => 125050, 'stock' => '15.000',
            'status' => ProductStatus::Draft, 'featured' => false];
    }

    public function variable(): static
    {
        return $this->state(fn () => ['product_type' => ProductType::Variable, 'price' => null, 'stock' => null]);
    }

    public function inquiry(): static
    {
        return $this->state(fn () => ['purchase_mode' => PurchaseMode::Inquiry, 'price' => null, 'stock' => null]);
    }
}
