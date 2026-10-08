<?php

namespace Database\Factories;

use App\Models\Stakeholder;
use Illuminate\Database\Eloquent\Factories\Factory;

class StakeholderFactory extends Factory
{
    protected $model = Stakeholder::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['client', 'supplier', 'partner', 'regulator', 'employee', 'shareholder']),
            'relevance_degree' => fake()->randomElement(['high', 'medium', 'low']),
            'needs_expectations' => fake()->sentence(10),
            'requirements' => fake()->sentence(8),
            'actions' => fake()->sentence(12),
            'responsible_id' => null, // Can be set manually
            'deadline' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'site_id' => \App\Models\Site::factory(),
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'client',
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'supplier',
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'relevance_degree' => 'high',
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'relevance_degree' => 'medium',
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'relevance_degree' => 'low',
        ]);
    }
}
