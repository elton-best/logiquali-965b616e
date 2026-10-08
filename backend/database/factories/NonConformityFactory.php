<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des non-conformités de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NonConformity>
 */
class NonConformityFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'process_id' => Process::factory(),
            'title' => 'NC: ' . fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['normative', 'legal', 'regulatory', 'contractual', 'procedural']),
            'severity' => fake()->randomElement(['minor', 'major', 'critical']),
            'source' => fake()->randomElement(['audit', 'complaint', 'internal', 'external', 'risk', 'process']),
            'root_cause_analysis' => fake()->sentence(),
            'corrective_action' => fake()->paragraph(),
            'responsible_id' => User::factory(),
            'deadline' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'verified', 'closed']),
            'detected_by' => User::factory(),
        ];
    }

    /**
     * NC majeure
     */
    public function major(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'major',
        ]);
    }

    /**
     * NC fermée
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
