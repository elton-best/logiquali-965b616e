<?php

namespace Database\Factories;

use App\Models\Context;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContextFactory extends Factory
{
    protected $model = Context::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['swot', 'pestel']);

        $baseData = [
            'title' => fake()->sentence(4),
            'year' => fake()->numberBetween(2020, 2030),
            'type' => $type,
            'site_id' => \App\Models\Site::factory(),
            'category' => fake()->randomElement(['economic', 'social', 'technological', 'environmental', 'legal', 'political']),
            'description' => fake()->paragraph(2),
            'impact' => fake()->randomElement(['positive', 'negative', 'neutral']),
        ];

        if ($type === 'swot') {
            return array_merge($baseData, [
                'swot_strengths' => fake()->paragraph(3),
                'swot_weaknesses' => fake()->paragraph(3),
                'swot_opportunities' => fake()->paragraph(3),
                'swot_threats' => fake()->paragraph(3),
            ]);
        }

        return array_merge($baseData, [
            'pestel_political' => fake()->sentence(10),
            'pestel_economic' => fake()->sentence(10),
            'pestel_social' => fake()->sentence(10),
            'pestel_technological' => fake()->sentence(10),
            'pestel_environmental' => fake()->sentence(10),
            'pestel_legal' => fake()->sentence(10),
        ]);
    }

    public function swot(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'swot',
            'swot_strengths' => fake()->paragraph(2),
            'swot_weaknesses' => fake()->paragraph(2),
            'swot_opportunities' => fake()->paragraph(2),
            'swot_threats' => fake()->paragraph(2),
            'pestel_political' => null,
            'pestel_economic' => null,
            'pestel_social' => null,
            'pestel_technological' => null,
            'pestel_environmental' => null,
            'pestel_legal' => null,
        ]);
    }

    public function pestel(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pestel',
            'swot_strengths' => null,
            'swot_weaknesses' => null,
            'swot_opportunities' => null,
            'swot_threats' => null,
            'pestel_political' => fake()->sentence(8),
            'pestel_economic' => fake()->sentence(8),
            'pestel_social' => fake()->sentence(8),
            'pestel_technological' => fake()->sentence(8),
            'pestel_environmental' => fake()->sentence(8),
            'pestel_legal' => fake()->sentence(8),
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }
}
