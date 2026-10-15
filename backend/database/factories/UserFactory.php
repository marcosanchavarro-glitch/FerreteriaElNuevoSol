<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(), 'phone' => null,
            'password' => 'SoloDesarrollo!2026', 'role' => UserRole::Customer, 'active' => true,
            'email_verified_at' => null];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function seller(): static
    {
        return $this->state(fn () => ['role' => UserRole::Seller]);
    }
}
