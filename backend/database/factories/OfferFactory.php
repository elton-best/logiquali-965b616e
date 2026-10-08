<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ref' => 'OFF-' . fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'price' => fake()->randomFloat(2, 100, 5000),
            'duration_months' => 12,
        ];
    }
}
