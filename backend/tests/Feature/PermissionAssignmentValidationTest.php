<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Module;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\PermissionNormMapping;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Norm $iso9001;
    protected Norm $iso50001;
    protected Module $contexte;
    protected Module $energie;
    protected Permission $permContexte;
    protected Permission $permEnergie;
    protected User $admin;
    protected Enterprise $enterprise;
    protected Site $site;
    protected Role $customRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create norms
        $this->iso9001 = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $this->iso50001 = Norm::create([
            'code' => 'ISO 50001:2018',
            'name' => 'Management de l\'énergie',
            'domain' => 'environment',
            'status' => 'published',
        ]);

        // Create modules
        $this->contexte = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $this->energie = Module::create([
            'code' => 'energie',
            'name' => 'Énergie',
            'iso_point' => 7,
        ]);

        // Create permissions
        $this->permContexte = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);

        $this->permEnergie = Permission::create([
            'name' => 'energie.manage',
            'guard_name' => 'web',
        ]);

        // Create mappings
        PermissionNormMapping::create([
            'permission_id' => $this->permContexte->id,
            'norm_id' => $this->iso9001->id,
            'module_id' => $this->contexte->id,
            'is_shared' => true,
        ]);

        PermissionNormMapping::create([
            'permission_id' => $this->permEnergie->id,
            'norm_id' => $this->iso50001->id,
            'module_id' => $this->energie->id,
            'is_shared' => false,
        ]);

        // Create enterprise, site, user
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        // Assign admin role
        $adminRole = Role::query()->where('name', 'admin_entreprise')->where('guard_name', 'web')->firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $rolesUpdatePermission = Permission::firstOrCreate([
            'name' => 'roles.update',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($rolesUpdatePermission);
        $this->admin->assignRole($adminRole);

        // Create custom role
        $this->customRole = Role::create([
            'name' => 'custom_role_' . uniqid(),
            'guard_name' => 'web',
            'enterprise_id' => $this->enterprise->id,
        ]);

        // Subscribe to ISO-9001 only (offer-driven model)
        $offer = Offer::factory()->create(['is_active' => true]);
        $offer->norms()->syncWithoutDetaching([$this->iso9001->id]);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->site->id,
            'offer_id' => $offer->id,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Assign permission from subscribed norm (should succeed)
     */
    public function test_assign_permission_from_subscribed_norm_succeeds()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/roles/' . $this->customRole->id . '/permissions', [
            'permissions' => [$this->permContexte->id],
        ]);

        $response->assertStatus(200);
        $this->assertTrue($this->customRole->hasPermissionTo($this->permContexte->name));
    }

    /**
     * Test 2: Assign permission from unsubscribed norm (should fail)
     */
    public function test_assign_permission_from_unsubscribed_norm_fails()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/roles/' . $this->customRole->id . '/permissions', [
            'permissions' => [$this->permEnergie->id],
        ]);

        $response->assertStatus(422);
        $this->assertTrue(
            is_string($response->json('error')) || is_string($response->json('message')),
            'La réponse 422 doit contenir "error" ou "message".'
        );
    }

    /**
     * Test 3: Non-admin cannot assign permissions
     */
    public function test_non_admin_cannot_assign_permissions()
    {
        $regularUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->actingAs($regularUser);

        $response = $this->postJson('/api/v1/roles/' . $this->customRole->id . '/permissions', [
            'permissions' => [$this->permContexte->id],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 4: Cannot assign permission to role of different enterprise
     */
    public function test_cannot_assign_permission_to_other_enterprise_role()
    {
        $otherEnterprise = Enterprise::factory()->create();
        $otherRole = Role::create([
            'name' => 'other_role',
            'guard_name' => 'web',
            'enterprise_id' => $otherEnterprise->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/roles/' . $otherRole->id . '/permissions', [
            'permissions' => [$this->permContexte->id],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 5: Multiple permissions - mixed valid/invalid (should fail)
     */
    public function test_mixed_valid_invalid_permissions_fails()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/roles/' . $this->customRole->id . '/permissions', [
            'permissions' => [
                $this->permContexte->id,  // Valid (subscribed)
                $this->permEnergie->id,   // Invalid (not subscribed)
            ],
        ]);

        $response->assertStatus(422);
    }

}
