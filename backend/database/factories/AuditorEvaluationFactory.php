<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\AuditorEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditorEvaluation>
 */
class AuditorEvaluationFactory extends Factory
{
    protected $model = AuditorEvaluation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auditor_id' => User::factory()->create(['user_type' => 'company']),
            'audit_id' => Audit::factory(),
            'evaluator_id' => User::factory()->create(['user_type' => 'super_admin']),
            'evaluation_date' => now(),
            
            // Compétences techniques (ISO 9001 §7.2)
            'technical_knowledge_score' => $this->faker->numberBetween(3, 5),
            'iso_knowledge_score' => $this->faker->numberBetween(3, 5),
            'audit_techniques_score' => $this->faker->numberBetween(3, 5),
            
            // Compétences comportementales
            'communication_score' => $this->faker->numberBetween(3, 5),
            'objectivity_score' => $this->faker->numberBetween(4, 5),
            'professional_conduct_score' => $this->faker->numberBetween(4, 5),
            
            // Résultat global
            'total_score' => null, // Calculé automatiquement
            'is_qualified' => true,
            'independence_verified' => true,
            'independence_notes' => 'Aucun conflit d\'intérêt identifié',
            
            'comments' => $this->faker->paragraph(),
            'recommendations' => $this->faker->sentence(),
            'next_evaluation_date' => now()->addYear(),
            'certifications' => [
                'Auditeur interne ISO 9001:2015',
                'Formation techniques d\'audit'
            ],
            'audit_experience_years' => $this->faker->numberBetween(2, 10),
        ];
    }

    /**
     * Auditeur hautement qualifié
     */
    public function excellent(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_knowledge_score' => 5,
            'iso_knowledge_score' => 5,
            'audit_techniques_score' => 5,
            'communication_score' => 5,
            'objectivity_score' => 5,
            'professional_conduct_score' => 5,
            'is_qualified' => true,
            'independence_verified' => true,
            'comments' => 'Auditeur exemplaire, très professionnel',
            'certifications' => [
                'Auditeur interne ISO 9001:2015',
                'Auditeur interne ISO 14001:2015',
                'Auditeur interne ISO 45001:2018',
                'Lead Auditor IRCA'
            ],
            'audit_experience_years' => $this->faker->numberBetween(8, 15),
        ]);
    }

    /**
     * Auditeur débutant
     */
    public function junior(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_knowledge_score' => 3,
            'iso_knowledge_score' => 3,
            'audit_techniques_score' => 3,
            'communication_score' => 4,
            'objectivity_score' => 4,
            'professional_conduct_score' => 4,
            'is_qualified' => true,
            'comments' => 'Bon potentiel, nécessite accompagnement',
            'recommendations' => 'Formation complémentaire sur techniques d\'audit recommandée',
            'certifications' => [
                'Auditeur interne ISO 9001:2015'
            ],
            'audit_experience_years' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Auditeur non qualifié
     */
    public function notQualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_knowledge_score' => 2,
            'iso_knowledge_score' => 2,
            'audit_techniques_score' => 2,
            'communication_score' => 3,
            'objectivity_score' => 3,
            'professional_conduct_score' => 3,
            'is_qualified' => false,
            'comments' => 'Compétences insuffisantes pour audits autonomes',
            'recommendations' => 'Formation ISO 9001 et techniques d\'audit obligatoire',
            'certifications' => [],
            'audit_experience_years' => 0,
        ]);
    }

    /**
     * Problème d'indépendance
     */
    public function notIndependent(): static
    {
        return $this->state(fn (array $attributes) => [
            'independence_verified' => false,
            'independence_notes' => 'Conflit d\'intérêt détecté : responsable du processus audité',
            'is_qualified' => false,
        ]);
    }

    /**
     * Évaluation expirée
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_date' => now()->subYears(2),
            'next_evaluation_date' => now()->subYear(),
        ]);
    }
}
