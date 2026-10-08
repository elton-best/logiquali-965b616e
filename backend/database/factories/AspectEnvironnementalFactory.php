<?php

namespace Database\Factories;

use App\Models\AspectEnvironnemental;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class AspectEnvironnementalFactory extends Factory
{
    protected $model = AspectEnvironnemental::class;

    public function definition(): array
    {
        $gravite = $this->faker->numberBetween(1, 5);
        $frequence = $this->faker->numberBetween(1, 5);
        $detectabilite = $this->faker->numberBetween(1, 5);
        
        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => Site::factory(),
            'type' => $this->faker->randomElement(['emission_air', 'rejet_eau', 'dechet', 'bruit', 'odeur']),
            'designation' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'condition' => $this->faker->randomElement(['normale', 'anormale', 'urgence']),
            'gravite' => $gravite,
            'frequence' => $frequence,
            'detectabilite' => $detectabilite,
            'aspect_significatif' => ($gravite * $frequence * $detectabilite) >= 50,
            'mesures_maitrise' => $this->faker->paragraph(),
        ];
    }
}