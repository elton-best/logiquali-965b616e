<?php

namespace Database\Factories;

use App\Models\Maintenance;
use App\Models\Equipement;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        return [
            'equipement_id' => Equipement::factory(),
            'type' => $this->faker->randomElement(['preventive', 'corrective', 'etalonnage']),
            'date_prevue' => $this->faker->dateTimeBetween('now', '+6 months'),
            'date_realisee' => null,
            'statut' => $this->faker->randomElement(['planifie', 'en_cours', 'realise', 'reporte', 'annule']),
            'description' => $this->faker->sentence(),
            'responsable' => $this->faker->name(),
            'observations' => null,
        ];
    }
}