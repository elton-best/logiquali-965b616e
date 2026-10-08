<?php

namespace Database\Factories;

use App\Models\ProcessVersion;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessVersionFactory extends Factory
{
    protected $model = ProcessVersion::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'version_number' => '1.0',
            'changes_description' => $this->faker->sentence(),
            'status' => 'draft',
            'author_user_id' => User::factory(),
            'verifier_user_id' => null,
            'approver_user_id' => null,
            'verified_at' => null,
            'approved_at' => null,
            'version_date' => now(),
            'is_current' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verifier_user_id' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'verifier_user_id' => User::factory(),
            'approver_user_id' => User::factory(),
            'verified_at' => now()->subDays(1),
            'approved_at' => now(),
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
