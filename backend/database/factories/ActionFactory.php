<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des actions de test
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Action>
 */
class ActionFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['corrective', 'preventive', 'improvement']);
        $deadline = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'site_id' => Site::factory(),
            'process_id' => Process::factory(),
            'initiator_id' => User::factory(),
            'type' => $type,
            'title' => ucfirst($type) . ' Action: ' . fake()->sentence(4),
            'description' => fake()->paragraph(),
            // 'cause' => fake()->sentence(),
            'expected_result' => fake()->sentence(),
            'responsible_id' => User::factory(),
            'deadline' => $deadline,
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed', 'verified', 'cancelled']),
        ];
    }

    /**
     * Action corrective
     */
    public function corrective(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'corrective',
        ]);
    }

    /**
     * Action d'amélioration
     */
    public function improvement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'improvement',
        ]);
    }
}
