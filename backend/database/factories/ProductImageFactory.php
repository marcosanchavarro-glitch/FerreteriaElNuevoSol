<?php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductImage> */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return ['product_id' => ProductFactory::new(), 'path' => 'demo/placeholder.jpg', 'alt_text' => 'Imagen de prueba, sin archivo adjunto', 'is_primary' => false, 'sort_order' => 0];
    }
}
