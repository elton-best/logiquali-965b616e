<?php

namespace Database\Factories;

use App\Models\ProcessIndicator;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessIndicatorFactory extends Factory
{
    protected $model = ProcessIndicator::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'code' => 'IND-' . strtoupper($this->faker->lexify('???')) . '-' . $this->faker->numberBetween(1, 999),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement(['efficacite', 'efficience', 'conformite', 'performance']),
            'category' => $this->faker->randomElement(['qualite', 'environnement', 'sante_securite', 'global']),
            'unit' => $this->faker->randomElement(['%', 'nb', '€', 'kg', 'h', 'j']),
            'measurement_frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'target_value' => $this->faker->numberBetween(50, 100),
            'alert_threshold' => $this->faker->optional()->numberBetween(30, 50),
            'responsible_user_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
