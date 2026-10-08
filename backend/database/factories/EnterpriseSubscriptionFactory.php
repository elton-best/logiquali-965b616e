<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnterpriseSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ref' => 'SUB-' . fake()->unique()->numberBetween(10000, 99999),
            'offer_id' => Offer::factory(),
            'site_id' => Site::factory(),
            'start_date' => now(),
            'expiration_date' => now()->addYear(),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
            'payment_status' => 'completed',
            'subscription_type' => 'primary',
            'amount_paid' => 0,
        ];
    }
}
