<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Politique QHSE',
                'code' => 'POL',
                'level' => 1,
                'description' => 'Documents de politique générale QHSE, vision stratégique de la direction',
                'retention_period_years' => 10,
                'required_approvers' => json_encode(['direction_generale', 'directeur_qhse']),
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Manuel SMI',
                'code' => 'MAN',
                'level' => 2,
                'description' => 'Manuel du Système de Management Intégré, description du système',
                'retention_period_years' => 10,
                'required_approvers' => json_encode(['directeur_qhse', 'site_manager']),
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Procédures',
                'code' => 'PROC',
                'level' => 3,
                'description' => 'Procédures système, processus métier, modes opératoires généraux',
                'retention_period_years' => 7,
                'required_approvers' => json_encode(['responsable_qhse', 'responsable_processus']),
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Instructions de travail',
                'code' => 'INST',
                'level' => 4,
                'description' => 'Instructions opérationnelles, consignes de sécurité, fiches de poste',
                'retention_period_years' => 5,
                'required_approvers' => json_encode(['responsable_service', 'manager']),
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Enregistrements',
                'code' => 'ENR',
                'level' => 5,
                'description' => 'Formulaires remplis, preuves, rapports, comptes-rendus',
                'retention_period_years' => 3,
                'required_approvers' => json_encode(['operateur', 'technicien']),
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            DocumentCategory::create($category);
        }

        $this->command->info(' 5 catégories de documents QHSE créées avec succès');
    }
}
