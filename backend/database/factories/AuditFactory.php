<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des audits de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Audit>
 */
class AuditFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plannedDate = fake()->dateTimeBetween('+1 month', '+6 months');
        
        return [
            'site_id' => Site::factory(),
            'type' => fake()->randomElement(['internal', 'external', 'certification']),
            'title' => 'Audit ' . fake()->words(3, true),
            'scope' => fake()->sentence(),
            'lead_auditor_id' => User::factory(),
            'planned_date' => $plannedDate,
            'actual_date' => null,
            'duration' => fake()->numberBetween(1, 8),
            'status' => 'planned',
            'report_path' => null,
        ];
    }

    /**
     * Audit complété
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $actualDate = fake()->dateTimeBetween($attributes['planned_date'], 'now');
            return [
                'status' => 'completed',
                'actual_date' => $actualDate,
                'report_path' => 'audits/report_' . fake()->uuid() . '.pdf',
            ];
        });
    }
}
