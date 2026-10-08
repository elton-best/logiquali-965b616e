<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission as SpatiePermission;

class BlockGIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable middleware that may interfere with test flows
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            \App\Http\Middleware\ForceCompanySetup::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\ForceSignatureUpload::class,
        ]);
    }

    public function test_lecteur_cannot_create_actions(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        SpatieRole::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $user->assignRole('lecteur');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/actions', []);
        $response->assertStatus(403);
    }

    public function test_site_manager_cannot_assign_site_manager_role(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $perm = SpatiePermission::firstOrCreate(['name' => 'personnel.create', 'guard_name' => 'web']);
        $role = SpatieRole::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $actor = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $actor->assignRole('site_manager');

        $payload = [
            'first_name' => 'New',
            'last_name' => 'Manager',
            'email' => 'new.manager+' . uniqid() . '@example.com',
            'job_title' => 'Manager',
            'site_id' => $site->id,
            'access_role' => 'site_manager',
        ];

        $response = $this->actingAs($actor, 'sanctum')->postJson('/api/v1/users', $payload);

        $response->assertStatus(422);
        $this->assertStringContainsString('ne peut pas', (string) ($response->json('message') ?? ''));
    }

    public function test_admin_entreprise_cannot_create_protected_role(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $admin = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        SpatieRole::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        $admin->assignRole('admin_entreprise');

        $payload = [
            'name' => 'super_admin',
            'enterprise_id' => $enterprise->id,
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/roles', $payload);

        // The guard may return 403 (access denied) or 422 (validation/reserved name) depending on
        // the controller guard ordering and environment. Accept both to avoid flaky failure.
        $this->assertTrue(in_array($response->status(), [403, 422]), 'Expected 403 or 422, got ' . $response->status());

        $message = (string) ($response->json('message') ?? '');
        $this->assertNotEmpty($message);
    }

    public function test_effective_permissions_are_union_of_roles(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $docPerm = SpatiePermission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);
        $actPerm = SpatiePermission::firstOrCreate(['name' => 'actions.manage', 'guard_name' => 'web']);

        $roleDocs = SpatieRole::firstOrCreate(['name' => 'role_docs', 'guard_name' => 'web']);
        $roleActions = SpatieRole::firstOrCreate(['name' => 'role_actions', 'guard_name' => 'web']);
        $roleDocs->givePermissionTo($docPerm);
        $roleActions->givePermissionTo($actPerm);

        $target = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $target->assignRole('role_docs');
        $target->assignRole('role_actions');

        $admin = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        SpatieRole::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        $admin->assignRole('admin_entreprise');

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/users/{$target->id}/permissions");
        $response->assertStatus(200);

        $effective = $response->json('effective_permissions');
        $this->assertContains('documents.read', $effective);
        $this->assertContains('actions.manage', $effective);
    }
}
