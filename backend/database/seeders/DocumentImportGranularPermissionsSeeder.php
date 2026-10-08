<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DocumentImportGranularPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'import_documents.own_site',
                'guard_name' => 'web',
                'description' => 'Importer des documents pour son propre site uniquement',
            ],
            [
                'name' => 'import_documents.all_sites',
                'guard_name' => 'web',
                'description' => 'Importer des documents pour tous les sites de l\'entreprise',
            ],
            [
                'name' => 'import_documents.rollback',
                'guard_name' => 'web',
                'description' => 'Annuler (rollback) des imports de documents',
            ],
            [
                'name' => 'import_documents.bulk',
                'guard_name' => 'web',
                'description' => 'Importer en masse (> 100 lignes)',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => $permissionData['guard_name']],
                ['description' => $permissionData['description']]
            );
        }

        $this->command->info('✅ Permissions granulaires d\'import de documents créées');
    }
}
