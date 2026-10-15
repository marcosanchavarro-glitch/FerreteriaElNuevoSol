<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'slug' => fake()->unique()->slug().'-'.fake()->uuid(), 'active' => true, 'sort_order' => 0];
    }
}
