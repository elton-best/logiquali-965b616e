<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditFinding>
 */
class AuditFindingFactory extends Factory
{
    protected $model = AuditFinding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['nc_major', 'nc_minor', 'observation', 'opportunity']);
        
        return [
            'audit_id' => Audit::factory(),
            'type' => $type,
            'title' => $this->getTitle($type),
            'description' => $this->faker->paragraph(2),
            'evidence' => $this->faker->sentence(),
            'requirement' => $this->getRequirement($type),
            'clause_iso' => $this->faker->randomElement(['4.4', '6.1', '7.2', '8.1', '8.4', '9.1', '9.2', '10.2']),
            'process_id' => null, // Peut être lié à un processus
            'location' => $this->faker->randomElement([
                'Atelier production - Zone A',
                'Service Achats - Bureau R2',
                'Laboratoire qualité',
                'Entrepôt matières premières'
            ]),
            'qhse_axes' => ['quality'],
            'severity' => $this->getSeverity($type),
            'priority' => $this->getPriority($type),
            'root_cause' => $type !== 'opportunity' ? $this->faker->sentence() : null,
            'immediate_action' => $type !== 'opportunity' ? $this->faker->sentence() : null,
            'detected_at' => now(),
            'detected_by' => null,
            'status' => 'open',
            'resolution_deadline' => $this->getDeadline($type),
            'nc_created' => false,
            'non_conformity_id' => null,
            'resolved_at' => null,
            'attachments' => [],
        ];
    }

    /**
     * NC Majeure
     */
    public function ncMajor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'nc_major',
            'title' => 'Absence de procédure documentée critique',
            'severity' => 'high',
            'priority' => 1,
            'resolution_deadline' => now()->addDays(15),
        ]);
    }

    /**
     * NC Mineure
     */
    public function ncMinor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'nc_minor',
            'title' => 'Enregistrements incomplets',
            'severity' => 'medium',
            'priority' => 2,
            'resolution_deadline' => now()->addDays(30),
        ]);
    }

    /**
     * Observation
     */
    public function observation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'observation',
            'title' => 'Point d\'amélioration identifié',
            'severity' => 'low',
            'priority' => 3,
            'resolution_deadline' => now()->addDays(60),
            'root_cause' => null,
            'immediate_action' => null,
        ]);
    }

    /**
     * Opportunité
     */
    public function opportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'opportunity',
            'title' => 'Opportunité de digitalisation',
            'severity' => 'low',
            'priority' => 3,
            'resolution_deadline' => now()->addDays(90),
            'root_cause' => null,
            'immediate_action' => null,
        ]);
    }

    /**
     * Already resolved
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    // Helper methods
    private function getTitle(string $type): string
    {
        return match($type) {
            'nc_major' => $this->faker->randomElement([
                'Absence de procédure documentée',
                'Non-respect des exigences critiques ISO',
                'Défaillance du système de management'
            ]),
            'nc_minor' => $this->faker->randomElement([
                'Enregistrements incomplets',
                'Documentation périmée',
                'Écart mineur sur processus'
            ]),
            'observation' => $this->faker->randomElement([
                'Amélioration possible du processus',
                'Classement documents à optimiser',
                'Formation complémentaire suggérée'
            ]),
            'opportunity' => $this->faker->randomElement([
                'Digitalisation du workflow',
                'Automatisation possible',
                'Optimisation des délais'
            ]),
        };
    }

    private function getRequirement(string $type): string
    {
        if ($type === 'opportunity') {
            return '';
        }
        
        return $this->faker->randomElement([
            'ISO 9001:2015 § 4.4 - Système de management de la qualité',
            'ISO 9001:2015 § 7.2 - Compétence',
            'ISO 9001:2015 § 8.4 - Maîtrise des processus',
            'ISO 9001:2015 § 9.1 - Surveillance et mesure',
        ]);
    }

    private function getSeverity(string $type): string
    {
        return match($type) {
            'nc_major' => 'high',
            'nc_minor' => 'medium',
            'observation', 'opportunity' => 'low',
        };
    }

    private function getPriority(string $type): int
    {
        return match($type) {
            'nc_major' => 1,
            'nc_minor' => 2,
            'observation', 'opportunity' => 3,
        };
    }

    private function getDeadline(string $type): \DateTime
    {
        return match($type) {
            'nc_major' => now()->addDays(15),
            'nc_minor' => now()->addDays(30),
            'observation' => now()->addDays(60),
            'opportunity' => now()->addDays(90),
        };
    }
}
