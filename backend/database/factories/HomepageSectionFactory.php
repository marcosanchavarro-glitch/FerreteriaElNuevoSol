<?php

namespace Database\Factories;

use App\Enums\HomepageSectionType;
use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HomepageSection> */
class HomepageSectionFactory extends Factory
{
    protected $model = HomepageSection::class;

    public function definition(): array
    {
        return ['type' => HomepageSectionType::Hero, 'title' => 'Sección de prueba', 'settings' => ['alignment' => 'left'], 'sort_order' => 0, 'active' => false];
    }
}
