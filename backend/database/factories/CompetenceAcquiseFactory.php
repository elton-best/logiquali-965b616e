<?php

namespace Database\Factories;

use App\Models\CompetenceAcquise;
use App\Models\CompetenceRequise;
use App\Models\Enterprise;
use App\Models\Formation;
use App\Models\Habilitation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetenceAcquiseFactory extends Factory
{
    protected $model = CompetenceAcquise::class;

    public function definition(): array
    {
        $levels = ['base', 'intermediaire', 'avance', 'expert'];
        $methods = ['formation', 'experience', 'certification', 'evaluation'];
        $statuses = ['active', 'expired', 'pending_renewal'];

        return [
            'enterprise_id' => Enterprise::factory(),
            'site_id' => Site::factory(),
            'user_id' => User::factory(),
            'competence_requise_id' => CompetenceRequise::factory(),
            'level_acquired' => $this->faker->randomElement($levels),
            'acquired_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => $this->faker->optional(0.7)->dateTimeBetween('+6 months', '+3 years'),
            'acquisition_method' => $this->faker->randomElement($methods),
            'formation_id' => $this->faker->optional(0.4)->randomElement([Formation::factory(), null]),
            'habilitation_id' => $this->faker->optional(0.3)->randomElement([Habilitation::factory(), null]),
            'notes' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement($statuses)
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function fromFormation(): static
    {
        return $this->state(fn (array $attributes) => [
            'acquisition_method' => 'formation',
            'formation_id' => Formation::factory(),
        ]);
    }

    public function fromHabilitation(): static
    {
        return $this->state(fn (array $attributes) => [
            'acquisition_method' => 'certification',
            'habilitation_id' => Habilitation::factory(),
        ]);
    }
}