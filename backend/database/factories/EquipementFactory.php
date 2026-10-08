<?php

namespace Database\Factories;

use App\Models\Equipement;
use App\Models\CodificationElement;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    public function definition(): array
    {
        return [
            'code_complet' => $this->faker->unique()->regexify('EQ[0-9]{3}'),
            'categorie_id' => CodificationElement::factory()->categorie(),
            'localisation_id' => CodificationElement::factory()->localisation(),
            'nom_commun' => $this->faker->words(2, true),
            'nom_commun_abrege' => $this->faker->regexify('[A-Z]{3}'),
            'indice' => $this->faker->regexify('[0-9]{3}'),
            'annee_acquisition' => $this->faker->year(),
            'marque' => $this->faker->company(),
            'modele' => $this->faker->word(),
            'numero_serie' => $this->faker->regexify('[A-Z0-9]{10}'),
            'etat' => $this->faker->randomElement(['tres_bon', 'bon', 'mauvais']),
            'valeur_acquisition' => $this->faker->randomFloat(2, 1000, 50000),
            'observations' => $this->faker->sentence(),
            'necessite_maintenance' => $this->faker->boolean(),
            'frequence_maintenance_jours' => $this->faker->numberBetween(30, 365),
            'actif' => true,
            'enterprise_id' => 1,
            'site_id' => 1,
        ];
    }
}