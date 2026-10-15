<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return ['title' => 'Banner de prueba', 'image_path' => 'demo/banner.jpg', 'active' => false, 'sort_order' => 0];
    }
}
