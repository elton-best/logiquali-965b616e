<?php

namespace Database\Factories;

use App\Models\AuditProgram;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditProgram>
 */
class AuditProgramFactory extends Factory
{
    protected $model = AuditProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2024, 2026);
        
        return [
            'site_id' => Site::factory(),
            'year' => $year,
            'title' => "Programme Audits Internes {$year}",
            'program_manager_id' => User::factory()->create(['user_type' => 'super_admin']),
            'objectives' => $this->faker->paragraph(3),
            'scope' => 'Tous les processus QSE',
            'target_processes' => ['achats', 'production', 'ventes'],
            'target_sites' => null,
            'risk_based_criteria' => [
                'Criticité risque >= 12',
                'Processus jamais audités',
                'Processus critiques'
            ],
            'planned_audits_count' => $this->faker->numberBetween(8, 15),
            'completed_audits_count' => 0,
            'status' => 'draft',
            'validated_at' => null,
            'validated_by' => null,
            'avg_conformity_rate' => null,
            'nc_major_count' => 0,
            'nc_minor_count' => 0,
            'observations_count' => 0,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the program is validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => User::factory()->create(['user_type' => 'super_admin']),
        ]);
    }

    /**
     * Indicate that the program is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'validated_at' => now()->subMonths(6),
            'validated_by' => User::factory()->create(['user_type' => 'super_admin']),
            'completed_audits_count' => $attributes['planned_audits_count'],
            'avg_conformity_rate' => $this->faker->numberBetween(75, 95),
            'nc_major_count' => $this->faker->numberBetween(0, 3),
            'nc_minor_count' => $this->faker->numberBetween(2, 8),
            'observations_count' => $this->faker->numberBetween(5, 15),
        ]);
    }

    /**
     * Program for a specific year
     */
    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
            'title' => "Programme Audits Internes {$year}",
        ]);
    }
}
