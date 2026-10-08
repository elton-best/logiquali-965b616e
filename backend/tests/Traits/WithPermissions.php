<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Permission;

trait WithPermissions
{
    protected function setUpPermissions(): void
    {
        $permissions = [
            'view_nomenclature',
            'configure_nomenclature',
            'view_documents',
            'create_documents',
            'verify_documents',
            'approve_documents',
            'import_documents',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
