<?php

namespace Database\Factories;

use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeConfigurationFactory extends Factory
{
    protected $model = DocumentTypeConfiguration::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => null,
            'name' => $this->faker->words(2, true),
            'abbreviation' => strtoupper($this->faker->lexify('???')),
            'abbreviation_length' => 3,
            'scope' => 'site',
            'is_active' => true,
            'description' => $this->faker->sentence(),
        ];
    }

    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
    }

    public function forEnterprise(Enterprise $enterprise): static
    {
        return $this->state(fn (array $attributes) => [
            'enterprise_id' => $enterprise->id,
            'site_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
