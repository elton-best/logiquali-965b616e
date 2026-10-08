<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des rôles de test
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = [
            'Responsable Qualité',
            'Pilote de Processus',
            'Auditeur Interne',
            'Référent Site',
            'Collaborateur'
        ];
        
        return [
            'name' => fake()->unique()->randomElement($roles),
            'description' => fake()->sentence(),
        ];
    }
}
