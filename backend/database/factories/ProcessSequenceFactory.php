<?php

namespace Database\Factories;

use App\Models\ProcessSequence;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessSequenceFactory extends Factory
{
    protected $model = ProcessSequence::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'sequence_order' => $this->faker->numberBetween(1, 10),
            'input_description' => $this->faker->sentence(),
            'activity_description' => $this->faker->sentence(),
            'output_description' => $this->faker->sentence(),
            'responsible_user_id' => null,
            'duration_minutes' => $this->faker->optional()->numberBetween(15, 120),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function withResponsible(): static
    {
        return $this->state(fn (array $attributes) => [
            'responsible_user_id' => User::factory(),
        ]);
    }

    public function withDuration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $minutes,
        ]);
    }
}
