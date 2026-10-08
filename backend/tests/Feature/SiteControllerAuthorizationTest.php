<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use App\Http\Middleware\CheckSubscriptionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteControllerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Enterprise $enterprise;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate authorization behavior for site creation from subscription gating (423).
        $this->withoutMiddleware(CheckSubscriptionStatus::class);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $siteManagerRole = Role::firstOrCreate([
            'name' => 'site_manager',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => 'sites.create',
            'guard_name' => 'web',
        ]);

        // Intentionally grant create permission to both roles to validate policy hard-guard.
        $adminRole->givePermissionTo('sites.create');
        $siteManagerRole->givePermissionTo('sites.create');
    }

    public function test_enterprise_admin_can_create_site(): void
    {
        $admin = User::factory()->createOne([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $admin->assignRole('admin_entreprise');

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/sites', [
            'name' => 'Site Test Admin',
            'location' => 'Cotonou',
            'city' => 'Cotonou',
            'is_headquarter' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sites', [
            'name' => 'Site Test Admin',
            'enterprise_id' => $this->enterprise->id,
        ]);
    }

    public function test_site_manager_cannot_create_site_even_with_sites_create_permission(): void
    {
        $siteManager = User::factory()->createOne([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $siteManager->assignRole('site_manager');

        $this->actingAs($siteManager, 'sanctum');

        $response = $this->postJson('/api/v1/sites', [
            'name' => 'Site Interdit Site Manager',
            'location' => 'Porto-Novo',
            'city' => 'Porto-Novo',
            'is_headquarter' => false,
            'is_active' => true,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('sites', [
            'name' => 'Site Interdit Site Manager',
            'enterprise_id' => $this->enterprise->id,
        ]);
    }
}
