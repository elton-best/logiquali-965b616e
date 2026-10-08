<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DocumentCodeRecyclingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'release_own_codes',
                'guard_name' => 'sanctum',
                'description' => 'Libérer ses propres codes de documents',
            ],
            [
                'name' => 'release_all_codes',
                'guard_name' => 'sanctum',
                'description' => 'Libérer tous les codes de documents de l\'entreprise',
            ],
            [
                'name' => 'reuse_recycled_codes',
                'guard_name' => 'sanctum',
                'description' => 'Réutiliser des codes recyclés',
            ],
            [
                'name' => 'view_code_history',
                'guard_name' => 'sanctum',
                'description' => 'Consulter l\'historique des codes',
            ],
            [
                'name' => 'force_release_codes',
                'guard_name' => 'sanctum',
                'description' => 'Forcer la libération de codes (admin)',
            ],
        ];

        foreach ($permissions as $permissionData) {
            foreach (['sanctum', 'web'] as $guard) {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name'], 'guard_name' => $guard],
                    ['description' => $permissionData['description'] ?? null]
                );
            }
        }

        $this->command->info('✅ Permissions de recyclage de codes créées');
    }
}
