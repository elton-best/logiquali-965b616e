<?php

namespace Database\Factories;

use App\Models\ManagementReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManagementReviewFactory extends Factory
{
    protected $model = ManagementReview::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2030);
        $quarter = fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']);

        return [
            'title' => "Revue Direction {$quarter} {$year}",
            'scheduled_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'planned_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed', 'reported']),
            'year' => $year,
            'quarter' => $quarter,
            'site_id' => \App\Models\Site::factory(),
            'chairman_id' => \App\Models\User::factory(),
            'kpi_data' => json_encode([
                'quality_rate' => fake()->randomFloat(2, 85, 99),
                'customer_satisfaction' => fake()->randomFloat(2, 75, 95),
                'delivery_time' => fake()->randomFloat(1, 1, 10),
            ]),
            'objectives_data' => json_encode([
                'achieved' => fake()->numberBetween(5, 15),
                'in_progress' => fake()->numberBetween(2, 8),
                'delayed' => fake()->numberBetween(0, 3),
            ]),
            'actions_data' => json_encode([
                'completed' => fake()->numberBetween(10, 30),
                'ongoing' => fake()->numberBetween(5, 15),
                'overdue' => fake()->numberBetween(0, 5),
            ]),
            'risks_data' => json_encode([
                'critical' => fake()->numberBetween(0, 3),
                'high' => fake()->numberBetween(2, 8),
                'medium' => fake()->numberBetween(5, 15),
                'low' => fake()->numberBetween(10, 30),
            ]),
            'nc_data' => json_encode([
                'opened' => fake()->numberBetween(5, 20),
                'closed' => fake()->numberBetween(3, 15),
                'pending' => fake()->numberBetween(1, 5),
            ]),
            'audit_data' => json_encode([
                'planned' => fake()->numberBetween(4, 12),
                'completed' => fake()->numberBetween(2, 8),
                'findings' => fake()->numberBetween(0, 10),
            ]),
            'decisions' => fake()->paragraph(5),
            'action_items' => fake()->paragraph(8),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'scheduled_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'scheduled_date' => now()->format('Y-m-d'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'scheduled_date' => fake()->dateTimeBetween('-3 months', '-1 week')->format('Y-m-d'),
            'decisions' => fake()->paragraph(6),
            'action_items' => fake()->paragraph(10),
        ]);
    }

    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reported',
            'scheduled_date' => fake()->dateTimeBetween('-6 months', '-1 month')->format('Y-m-d'),
            'decisions' => fake()->paragraph(6),
            'action_items' => fake()->paragraph(10),
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
            'title' => "Revue Direction {$attributes['quarter']} {$year}",
        ]);
    }

    public function forQuarter(string $quarter): static
    {
        return $this->state(fn (array $attributes) => [
            'quarter' => $quarter,
            'title' => "Revue Direction {$quarter} {$attributes['year']}",
        ]);
    }
}
