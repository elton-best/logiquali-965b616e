<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des entreprises de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enterprise>
 */
class EnterpriseFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();
        $companySlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $companyName));
        $uniqueSuffix = fake()->unique()->numerify('#######');
        
        return [
            'name' => $companyName,
            'sigle' => strtoupper(substr($companySlug, 0, 3)),
            'codification_mode' => 'standard',
            'email' => "{$companySlug}{$uniqueSuffix}@example.com",
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'industry' => fake()->randomElement([
                'Agroalimentaire',
                'Automobile',
                'Pharmaceutique',
                'Textile',
                'Technologie',
                'Services',
                'Construction',
                'Énergie'
            ]),
            'size' => fake()->randomElement(['small', 'medium', 'large']),
            'status' => 'pending',
            'approval_status' => 'pending',
            'created_by' => null,
            'owner_user_id' => null,
        ];
    }

    /**
     * Entreprise inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Petite entreprise
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'small',
        ]);
    }

    /**
     * Grande entreprise
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'large',
        ]);
    }

    /**
     * Entreprise en attente d'approbation
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /**
     * Entreprise approuvée
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    /**
     * Entreprise rejetée
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'approved_at' => now(),
        ]);
    }
}
