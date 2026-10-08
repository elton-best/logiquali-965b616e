<?php

namespace Database\Factories;

use App\Models\Process;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des risques de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Risk>
 */
class RiskFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $probability = fake()->numberBetween(1, 5);
        $gravity = fake()->numberBetween(1, 5);
        
        return [
            'site_id' => Site::factory(),
            'process_id' => Process::factory(),
            'title' => 'Risque: ' . fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['risk', 'opportunity']),
            'category' => fake()->randomElement(['strategic', 'operational', 'financial', 'compliance', 'safety', 'environmental', 'reputation', 'it', 'legal', 'other']),
            'probability' => $probability,
            'gravity' => $gravity,
            'impact' => $gravity,
            'criticality' => $probability * $gravity,
            'status' => fake()->randomElement(['identified', 'evaluated', 'treated', 'monitored']),
        ];
    }

    /**
     * Risque critique (haute probabilité + fort impact)
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'probability' => fake()->numberBetween(4, 5),
            'gravity' => fake()->numberBetween(4, 5),
            'impact' => fake()->numberBetween(4, 5),
        ]);
    }
}
