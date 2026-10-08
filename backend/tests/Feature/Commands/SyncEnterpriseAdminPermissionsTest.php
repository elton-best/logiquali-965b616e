<?php

namespace Tests\Feature\Commands;

use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use App\Services\RolePermissionBaselineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncEnterpriseAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_admin_role_and_clears_direct_permissions(): void
    {
        $baselineService = app(RolePermissionBaselineService::class);
        foreach (array_unique(array_merge(
            $baselineService->superAdminOnlyPermissions(),
            $baselineService->isoExtensionPermissions(),
            $baselineService->baselinePermissionNamesForRole('admin_entreprise'),
        )) as $permissionName) {
            if (!is_string($permissionName) || trim($permissionName) === '') {
                continue;
            }
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'users.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'offers.create', 'guard_name' => 'web']); // super-admin-only

        // Etat initial volontairement incorrect
        $role->syncPermissions(['users.read']);

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $admin = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
        ]);
        $admin->assignRole('admin_entreprise');
        $admin->givePermissionTo('documents.read');
        $this->assertSame(1, $admin->getDirectPermissions()->count());

        Artisan::call('permissions:sync-enterprise-admin');

        $role->refresh();
        $admin->refresh();

        $this->assertTrue($role->hasPermissionTo('users.read'));
        $this->assertTrue($role->hasPermissionTo('documents.read'));
        $this->assertFalse($role->hasPermissionTo('offers.create'));
        $this->assertSame(0, $admin->getDirectPermissions()->count());
    }
}
