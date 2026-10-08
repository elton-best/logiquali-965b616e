<?php

namespace Database\Factories;

use App\Models\Responsibility;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponsibilityFactory extends Factory
{
    protected $model = Responsibility::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'user_id' => User::factory(),
            'level' => $this->faker->randomElement(['Manager', 'Supervisor', 'Coordinator', 'Responsible']),
            'roles' => $this->faker->sentence(),
            'deliverables' => $this->faker->optional()->sentence(),
        ];
    }
}
