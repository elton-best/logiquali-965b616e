<?php

namespace Database\Factories;

use App\Models\Enterprise;
use App\Models\Formation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormationFactory extends Factory
{
    protected $model = Formation::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => Site::factory(),
            'designation' => $this->faker->sentence(3),
            'cibles' => [$this->faker->word(), $this->faker->word()],
            'target_user_ids' => [],
            'chronogramme' => array_fill(0, 12, false),
            'formateur' => $this->faker->name(),
            'cout' => $this->faker->randomFloat(2, 500, 5000),
            'date_debut' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'date_fin' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'period_mode' => 'custom',
            'period_label' => null,
            'frequency' => $this->faker->randomElement(['ponctuelle', 'annuelle', 'semestrielle']),
            'status' => $this->faker->randomElement(['planifiee', 'en_attente', 'realisee', 'replanifiee', 'annulee']),
            'created_by' => User::factory(),
            'organizer_user_id' => User::factory(),
        ];
    }
}
