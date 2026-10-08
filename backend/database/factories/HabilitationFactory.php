<?php

namespace Database\Factories;

use App\Models\Habilitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabilitationFactory extends Factory
{
    protected $model = Habilitation::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => \App\Models\Enterprise::factory(),
            'site_id' => \App\Models\Site::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['conduite', 'manipulation', 'securite']),
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->sentence(),
            'certificate_number' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
            'issued_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'issuing_authority' => $this->faker->company(),
            'status' => 'active',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function expired()
    {
        return $this->state([
            'status' => 'expired',
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function expiringSoon()
    {
        return $this->state([
            'expiry_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }
}