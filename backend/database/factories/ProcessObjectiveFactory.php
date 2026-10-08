<?php

namespace Database\Factories;

use App\Models\ProcessObjective;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessObjectiveFactory extends Factory
{
    protected $model = ProcessObjective::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'indicator_id' => null, // Must be provided when creating
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'target_value' => $this->faker->optional()->numberBetween(50, 100),
            'target_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['not_started', 'in_progress', 'achieved', 'failed']),
            'achievement_percentage' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'not_started',
            'achievement_percentage' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'achievement_percentage' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function achieved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'achieved',
            'achievement_percentage' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'achievement_percentage' => $this->faker->numberBetween(0, 50),
        ]);
    }
}
