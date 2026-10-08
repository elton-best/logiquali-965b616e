<?php

namespace Database\Factories;

use App\Models\CompetenceRequise;
use App\Models\Enterprise;
use App\Models\JobDescription;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetenceRequiseFactory extends Factory
{
    protected $model = CompetenceRequise::class;

    public function definition(): array
    {
        $types = ['technique', 'securite', 'qualite', 'reglementaire', 'management'];
        $levels = ['base', 'intermediaire', 'avance', 'expert'];
        $priorities = ['obligatoire', 'recommandee', 'optionnelle'];

        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => Site::factory(),
            'job_description_id' => JobDescription::factory(),
            'competence_type' => $this->faker->randomElement($types),
            'competence_name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'level_required' => $this->faker->randomElement($levels),
            'priority' => $this->faker->randomElement($priorities),
            'validity_months' => $this->faker->optional(0.6)->numberBetween(12, 60),
            'requires_certification' => $this->faker->boolean(30),
            'certification_authority' => $this->faker->optional()->company()
        ];
    }

    public function obligatoire(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'obligatoire',
        ]);
    }

    public function withCertification(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_certification' => true,
            'certification_authority' => $this->faker->company(),
        ]);
    }
}