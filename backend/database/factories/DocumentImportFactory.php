<?php

namespace Database\Factories;

use App\Models\DocumentImport;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentImportFactory extends Factory
{
    protected $model = DocumentImport::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'user_id' => User::factory(),
            'filename' => $this->faker->word() . '.xlsx',
            'file_path' => 'imports/' . $this->faker->uuid() . '.xlsx',
            'status' => 'pending',
            'validation_results' => null,
            'import_stats' => null,
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'imported_rows' => 0,
            'failed_rows' => 0,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function validating(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validating',
        ]);
    }

    public function validated(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
            'valid_rows' => 10,
            'invalid_rows' => 2,
            'validation_results' => [
                'valid_count' => 10,
                'invalid_count' => 2,
                'rows' => [],
            ],
        ]);
    }

    public function importing(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'importing',
            'started_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'imported_rows' => 10,
            'failed_rows' => 0,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'import_stats' => [
                'imported' => 10,
                'failed' => 0,
                'errors' => [],
            ],
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Import failed due to validation errors',
            'completed_at' => now(),
        ]);
    }

    public function rolledBack(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rolled_back',
        ]);
    }
}
