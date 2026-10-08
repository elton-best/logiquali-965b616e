<?php

namespace Database\Factories;

use App\Models\CodeSequence;
use App\Models\DocumentTypeConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeSequenceFactory extends Factory
{
    protected $model = CodeSequence::class;

    public function definition(): array
    {
        return [
            'document_type_configuration_id' => DocumentTypeConfiguration::factory(),
            'document_type_id' => null,
            'process_id' => null,
            'year' => null,
            'month' => null,
            'current_number' => 0,
            'available_numbers' => [],
        ];
    }

    public function withAvailableNumbers(array $numbers): static
    {
        return $this->state(fn (array $attributes) => [
            'available_numbers' => $numbers,
        ]);
    }

    public function withCurrentNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'current_number' => $number,
        ]);
    }
}
