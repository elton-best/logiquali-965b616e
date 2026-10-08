<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des processus de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Process>
 */
class ProcessFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['management', 'operational', 'support']);
        
        $titles = [
            'management' => ['Direction Stratégique', 'Management de la Qualité', 'Amélioration Continue'],
            'operational' => ['Production', 'Ventes', 'Livraison', 'Service Client'],
            'support' => ['Ressources Humaines', 'Informatique', 'Comptabilité', 'Maintenance']
        ];
        
        return [
            'site_id' => Site::factory(),
            'title' => fake()->randomElement($titles[$type]),
            'code' => 'PROC_' . strtoupper(fake()->unique()->lexify('???')),
            'type' => $type,
            'purpose' => fake()->paragraph(),
            'pilot_id' => User::factory(),
            'copilot_id' => fake()->boolean(30) ? User::factory() : null,
            'is_validated' => fake()->boolean(50),
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    /**
     * Processus de management
     */
    public function management(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'management',
        ]);
    }

    /**
     * Processus opérationnel
     */
    public function operational(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'operational',
        ]);
    }

    /**
     * Processus de support
     */
    public function support(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'support',
        ]);
    }
}
