<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des documents de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'process_id' => Process::factory(),
            'code' => 'DOC-' . fake()->unique()->numberBetween(100, 999),
            'ref' => 'DOC-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'version' => '1.0',
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'status' => fake()->randomElement(['draft', 'pending_approval', 'approved', 'obsolete']),
            'author_id' => \App\Models\User::factory(),
            'document_type_configuration_id' => \App\Models\DocumentTypeConfiguration::factory(),
        ];
    }

    /**
     * Document approuvé
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
