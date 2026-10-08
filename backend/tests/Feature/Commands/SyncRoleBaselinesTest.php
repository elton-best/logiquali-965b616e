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

class SyncRoleBaselinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_all_role_baselines_and_clears_admin_direct_permissions(): void
    {
        $baselineService = app(RolePermissionBaselineService::class);
        foreach (array_unique(array_merge(
            $baselineService->superAdminOnlyPermissions(),
            $baselineService->isoExtensionPermissions(),
            $baselineService->baselinePermissionNamesForRole('admin_entreprise'),
            $baselineService->baselinePermissionNamesForRole('site_manager'),
        )) as $permissionName) {
            if (!is_string($permissionName) || trim($permissionName) === '') {
                continue;
            }
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        Permission::firstOrCreate(['name' => 'users.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'offers.create', 'guard_name' => 'web']); // super-admin-only
        Permission::firstOrCreate(['name' => 'sites.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'securite.epi.read', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        $siteManagerRole = Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);

        // Etat volontairement incorrect.
        $adminRole->syncPermissions(['offers.create']);
        $siteManagerRole->syncPermissions([]);

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

        Artisan::call('permissions:sync-role-baselines');

        $adminRole->refresh();
        $siteManagerRole->refresh();
        $admin->refresh();

        $this->assertTrue($adminRole->hasPermissionTo('users.read'));
        $this->assertTrue($adminRole->hasPermissionTo('documents.read'));
        $this->assertTrue($adminRole->hasPermissionTo('roles.create'));
        $this->assertTrue($adminRole->hasPermissionTo('roles.update'));
        $this->assertTrue($adminRole->hasPermissionTo('roles.delete'));
        $this->assertFalse($adminRole->hasPermissionTo('offers.create'));

        $this->assertTrue($siteManagerRole->hasPermissionTo('sites.read'));
        $this->assertTrue($siteManagerRole->hasPermissionTo('securite.epi.read'));
        $this->assertFalse($siteManagerRole->hasPermissionTo('roles.create'));

        $this->assertSame(0, $admin->getDirectPermissions()->count());
    }
}
