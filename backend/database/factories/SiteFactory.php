<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'enterprise_id' => Enterprise::factory(),
            'name' => $this->faker->company() . ' Site',
            'location' => $this->faker->address(),
            'city' => $this->faker->city(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'is_headquarter' => false,
            'is_active' => true,
            'manager_id' => null,
        ];
    }

    public function headquarter()
    {
        return $this->state(['is_headquarter' => true]);
    }

    public function inactive()
    {
        return $this->state(['is_active' => false]);
    }
}