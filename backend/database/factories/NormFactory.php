<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ISO-' . fake()->numberBetween(1000, 9999),
            'name' => 'ISO ' . fake()->numberBetween(9001, 50001),
            'description' => fake()->sentence(),
            'domain' => fake()->randomElement(['quality', 'environment', 'security']),
            'status' => 'published',
        ];
    }
}
