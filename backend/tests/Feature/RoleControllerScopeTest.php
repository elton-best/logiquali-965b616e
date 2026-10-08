<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\SecurityAuditLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $enterpriseAdmin;
    private User $siteManager;
    private Role $ownCustomRole;
    private Role $otherEnterpriseCustomRole;

    protected function setUp(): void
    {
        parent::setUp();
        // Isoler strictement les assertions RBAC/anti-IDOR des middlewares transverses
        // (subscription, MFA step-up, setup guards, etc.).
        $this->withoutMiddleware();

        $enterpriseA = Enterprise::factory()->create();
        $siteA = Site::factory()->create(['enterprise_id' => $enterpriseA->id]);
        $enterpriseB = Enterprise::factory()->create();

        Permission::findOrCreate('roles.read', 'web');
        Permission::findOrCreate('roles.update', 'web');
        Permission::findOrCreate('roles.delete', 'web');
        Permission::findOrCreate('roles.create', 'web');

        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $this->enterpriseAdmin = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
        ]);
        $this->enterpriseAdmin->assignRole('admin_entreprise');
        $this->enterpriseAdmin->givePermissionTo([
            'roles.read',
            'roles.update',
            'roles.delete',
            'roles.create',
        ]);

        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        $this->siteManager = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
        ]);
        $this->siteManager->assignRole('site_manager');
        $this->siteManager->givePermissionTo([
            'roles.read',
            'roles.update',
            'roles.delete',
            'roles.create',
        ]);

        $this->ownCustomRole = Role::create([
            'name' => 'custom_enterprise_' . $enterpriseA->id . '_qualite',
            'guard_name' => 'web',
            'description' => 'Role custom A',
            'enterprise_id' => $enterpriseA->id,
        ]);

        $this->otherEnterpriseCustomRole = Role::create([
            'name' => 'custom_enterprise_' . $enterpriseB->id . '_hse',
            'guard_name' => 'web',
            'description' => 'Role custom B',
            'enterprise_id' => $enterpriseB->id,
        ]);
    }

    public function test_index_hides_other_enterprise_custom_roles_for_non_super_admin(): void
    {
        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->getJson('/api/v1/roles');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $this->ownCustomRole->name,
        ]);
        $response->assertJsonMissing([
            'name' => $this->otherEnterpriseCustomRole->name,
        ]);
    }

    public function test_show_blocks_access_to_other_enterprise_custom_role(): void
    {
        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->getJson('/api/v1/roles/' . $this->otherEnterpriseCustomRole->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'authorization',
            'action' => 'roles_scope_denied',
            'resource_type' => \Spatie\Permission\Models\Role::class,
            'resource_id' => $this->otherEnterpriseCustomRole->id,
            'user_id' => $this->enterpriseAdmin->id,
        ]);

        $log = SecurityAuditLog::query()
            ->where('action', 'roles_scope_denied')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('role_outside_actor_scope', data_get($log->metadata, 'reason'));
    }

    public function test_update_blocks_mutation_of_other_enterprise_custom_role(): void
    {
        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->putJson('/api/v1/roles/' . $this->otherEnterpriseCustomRole->id, [
                'description' => 'Attempted mutation',
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_blocks_deletion_of_other_enterprise_custom_role(): void
    {
        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->deleteJson('/api/v1/roles/' . $this->otherEnterpriseCustomRole->id);

        $response->assertStatus(403);
    }

    public function test_show_blocks_spoofed_custom_name_when_enterprise_scope_differs(): void
    {
        $spoofedRole = Role::create([
            'name' => 'custom_enterprise_' . $this->enterpriseAdmin->enterprise_id . '_spoofed',
            'guard_name' => 'web',
            'description' => 'Spoofed custom role',
            'enterprise_id' => Enterprise::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->getJson('/api/v1/roles/' . $spoofedRole->id);

        $response->assertStatus(403);
    }

    public function test_legacy_custom_role_without_enterprise_scope_is_hidden_and_cannot_be_mutated_until_migrated(): void
    {
        $legacyRole = Role::create([
            'name' => 'custom_enterprise_' . $this->enterpriseAdmin->enterprise_id . '_legacy',
            'guard_name' => 'web',
            'description' => 'Legacy custom role without enterprise_id',
            'enterprise_id' => null,
        ]);

        $showResponse = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->getJson('/api/v1/roles/' . $legacyRole->id);
        $showResponse->assertStatus(403);

        $updateResponse = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->putJson('/api/v1/roles/' . $legacyRole->id, [
                'description' => 'Attempted migration bypass',
            ]);

        $updateResponse->assertStatus(403);

        $log = SecurityAuditLog::query()
            ->whereIn('action', ['roles_scope_denied', 'roles_mutation_denied'])
            ->where('resource_id', $legacyRole->id)
            ->where('user_id', $this->enterpriseAdmin->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertContains(data_get($log->metadata, 'reason'), [
            'legacy_custom_role_without_enterprise_scope',
            'role_outside_actor_scope',
            'enterprise_scope_mismatch',
            'protected_system_role',
        ]);
    }

    public function test_site_manager_cannot_create_custom_role_even_with_roles_create_permission(): void
    {
        $roleName = 'custom_enterprise_' . $this->siteManager->enterprise_id . '_test_scope';
        $response = $this->actingAs($this->siteManager, 'sanctum')
            ->postJson('/api/v1/roles', [
                'name' => $roleName,
                'description' => 'Attempted custom role by site manager',
                'permissions' => ['users.read'],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('roles', ['name' => $roleName]);
    }
}
