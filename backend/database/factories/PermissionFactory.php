<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour générer des permissions de test
 * Note: En production, utiliser le PermissionsSeeder
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Définition de l'état par défaut du modèle
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $module = fake()->randomElement([
            'Processus', 'Documents', 'Audits', 'Actions', 'Risques'
        ]);
        $action = fake()->randomElement(['create', 'update', 'delete', 'view', 'validate']);
        
        return [
            'slug' => strtolower($module) . '.' . $action,
            'name' => ucfirst($action) . ' ' . $module,
            'description' => 'Permet de ' . $action . ' des ' . $module,
            'module' => 'Gestion des ' . $module,
        ];
    }
}
