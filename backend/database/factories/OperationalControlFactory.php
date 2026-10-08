<?php

namespace Database\Factories;

use App\Models\OperationalControl;
use Illuminate\Database\Eloquent\Factories\Factory;

class OperationalControlFactory extends Factory
{
    protected $model = OperationalControl::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => null,
            'process_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'control_points' => [
                ['point' => 'point 1', 'method' => 'check', 'frequency' => 'monthly', 'responsible' => 'Qualité'],
            ],
            'status' => 'active',
        ];
    }
}
