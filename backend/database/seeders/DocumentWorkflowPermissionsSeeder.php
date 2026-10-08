<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DocumentWorkflowPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions
        $permissions = [
            // Configuration nomenclature
            [
                'name' => 'configure_nomenclature',
                'description' => 'Configurer la nomenclature des codes documents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view_nomenclature',
                'description' => 'Consulter la configuration de nomenclature',
                'guard_name' => 'web',
            ],

            // Workflow vérification
            [
                'name' => 'verify_documents',
                'description' => 'Vérifier les documents soumis',
                'guard_name' => 'web',
            ],
            [
                'name' => 'submit_for_verification',
                'description' => 'Soumettre un document pour vérification',
                'guard_name' => 'web',
            ],

            // Workflow approbation
            [
                'name' => 'approve_documents',
                'description' => 'Approuver les documents vérifiés',
                'guard_name' => 'web',
            ],

            // Gestion documents
            [
                'name' => 'create_documents',
                'description' => 'Créer des documents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit_documents',
                'description' => 'Modifier des documents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete_documents',
                'description' => 'Supprimer des documents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view_documents',
                'description' => 'Consulter les documents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'import_documents',
                'description' => 'Importer des documents existants',
                'guard_name' => 'web',
            ],
            [
                'name' => 'export_documents',
                'description' => 'Exporter des documents',
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => $permissionData['guard_name']]
            );

            $this->command->info("✓ Permission '{$permissionData['name']}' créée");
        }

        // Assigner les permissions aux rôles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assigne les permissions aux rôles appropriés
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin : toutes les permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissions = Permission::where('guard_name', 'sanctum')->pluck('name')->toArray();
            $superAdmin->syncPermissions($permissions);
            $this->command->info("✓ Toutes les permissions assignées à 'super_admin'");
        }

        // Admin Entreprise : configuration + workflow complet
        $enterpriseAdmin = Role::where('name', 'enterprise_admin')->first();
        if ($enterpriseAdmin) {
            $enterpriseAdmin->syncPermissions([
                'configure_nomenclature',
                'view_nomenclature',
                'verify_documents',
                'approve_documents',
                'create_documents',
                'edit_documents',
                'delete_documents',
                'view_documents',
                'import_documents',
                'export_documents',
            ]);
            $this->command->info("✓ Permissions assignées à 'enterprise_admin'");
        }

        // Site manager : workflow + gestion documents (RBAC canonique)
        $siteManager = Role::where('name', 'site_manager')->where('guard_name', 'web')->first();
        if ($siteManager) {
            $siteManager->syncPermissions([
                'view_nomenclature',
                'verify_documents',
                'approve_documents',
                'create_documents',
                'edit_documents',
                'view_documents',
                'import_documents',
                'export_documents',
            ]);
            $this->command->info("✓ Permissions assignées à 'site_manager'");
        }

        // Vérificateur : vérification uniquement
        $verifier = Role::firstOrCreate(['name' => 'verifier', 'guard_name' => 'web']);
        $verifier->syncPermissions([
            'verify_documents',
            'view_documents',
        ]);
        $this->command->info("✓ Permissions assignées à 'verifier'");

        // Approbateur : approbation uniquement
        $approver = Role::firstOrCreate(['name' => 'approver', 'guard_name' => 'web']);
        $approver->syncPermissions([
            'approve_documents',
            'view_documents',
        ]);
        $this->command->info("✓ Permissions assignées à 'approver'");

        // Utilisateur standard : création + consultation
        $user = Role::where('name', 'user')->first();
        if ($user) {
            $user->syncPermissions([
                'create_documents',
                'view_documents',
                'submit_for_verification',
                'import_documents',
            ]);
            $this->command->info("✓ Permissions assignées à 'user'");
        }
    }
}
