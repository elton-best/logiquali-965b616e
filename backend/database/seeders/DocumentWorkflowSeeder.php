<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\DocumentWorkflow;
use Illuminate\Database\Seeder;

class DocumentWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Workflow pour Politique QHSE (Niveau 1) - Ultra strict
        $politique = DocumentCategory::where('code', 'POL')->first();
        if ($politique) {
            DocumentWorkflow::create([
                'document_category_id' => $politique->id,
                'name' => 'Workflow Politique QHSE',
                'steps' => [
                    [
                        'order' => 1,
                        'role' => 'responsable_qhse',
                        'name' => 'Responsable QHSE',
                        'required' => true,
                    ],
                    [
                        'order' => 2,
                        'role' => 'directeur_qhse',
                        'name' => 'Directeur QHSE',
                        'required' => true,
                    ],
                    [
                        'order' => 3,
                        'role' => 'direction_generale',
                        'name' => 'Direction Générale',
                        'required' => true,
                    ],
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // Workflow pour Manuel SMI (Niveau 2)
        $manuel = DocumentCategory::where('code', 'MAN')->first();
        if ($manuel) {
            DocumentWorkflow::create([
                'document_category_id' => $manuel->id,
                'name' => 'Workflow Manuel SMI',
                'steps' => [
                    [
                        'order' => 1,
                        'role' => 'site_manager',
                        'name' => 'Responsable Qualité',
                        'required' => true,
                    ],
                    [
                        'order' => 2,
                        'role' => 'directeur_qhse',
                        'name' => 'Directeur QHSE',
                        'required' => true,
                    ],
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // Workflow pour Procédures (Niveau 3)
        $procedures = DocumentCategory::where('code', 'PROC')->first();
        if ($procedures) {
            DocumentWorkflow::create([
                'document_category_id' => $procedures->id,
                'name' => 'Workflow Procédures Standard',
                'steps' => [
                    [
                        'order' => 1,
                        'role' => 'relecteur',
                        'name' => 'Relecteur',
                        'required' => false,
                    ],
                    [
                        'order' => 2,
                        'role' => 'responsable_processus',
                        'name' => 'Responsable Processus',
                        'required' => true,
                    ],
                    [
                        'order' => 3,
                        'role' => 'responsable_qhse',
                        'name' => 'Responsable QHSE',
                        'required' => true,
                    ],
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // Workflow pour Instructions (Niveau 4)
        $instructions = DocumentCategory::where('code', 'INST')->first();
        if ($instructions) {
            DocumentWorkflow::create([
                'document_category_id' => $instructions->id,
                'name' => 'Workflow Instructions de travail',
                'steps' => [
                    [
                        'order' => 1,
                        'role' => 'responsable_service',
                        'name' => 'Responsable Service',
                        'required' => true,
                    ],
                    [
                        'order' => 2,
                        'role' => 'manager',
                        'name' => 'Manager',
                        'required' => false,
                    ],
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // Workflow pour Enregistrements (Niveau 5) - Simplifié
        $enregistrements = DocumentCategory::where('code', 'ENR')->first();
        if ($enregistrements) {
            DocumentWorkflow::create([
                'document_category_id' => $enregistrements->id,
                'name' => 'Workflow Enregistrements',
                'steps' => [
                    [
                        'order' => 1,
                        'role' => 'operateur',
                        'name' => 'Opérateur/Technicien',
                        'required' => true,
                    ],
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ 5 workflows par défaut créés avec succès');
    }
}
