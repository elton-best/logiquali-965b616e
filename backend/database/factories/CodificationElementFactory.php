<?php

namespace Database\Factories;

use App\Models\CodificationElement;
use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodificationElementFactory extends Factory
{
    protected $model = CodificationElement::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => Enterprise::factory(),
            'type' => 'categorie',
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{2}'),
            'libelle' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'actif' => true,
        ];
    }

    public function categorie()
    {
        return $this->state(['type' => 'categorie']);
    }

    public function localisation()
    {
        return $this->state(['type' => 'localisation']);
    }
}
