<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailNotification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserControllerSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected $enterprise;
    protected $site;
    protected $adminUser;
    protected $siteManagerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate user management behavior from global blocking middleware (423).
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);

        // Créer la structure de base
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        // Créer les rôles et permissions nécessaires
        $adminRole = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        $siteManagerRole = Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        Permission::create(['name' => 'personnel.read', 'guard_name' => 'web']);
        Permission::create(['name' => 'personnel.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'personnel.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'personnel.delete', 'guard_name' => 'web']);

        $adminRole->givePermissionTo(['personnel.read', 'personnel.create', 'personnel.update', 'personnel.delete']);
        $siteManagerRole->givePermissionTo(['personnel.read', 'personnel.create', 'personnel.update']);

        // Créer l'utilisateur admin
        $this->adminUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);
        $this->adminUser->assignRole('admin_entreprise');

        $this->siteManagerUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $this->siteManagerUser->assignRole('site_manager');
    }

    public function test_admin_can_list_users()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Créer quelques utilisateurs dans la même entreprise
        User::factory()->count(2)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_collaborator_with_personnel_read_can_list_users(): void
    {
        $readerRole = Role::firstOrCreate(['name' => 'qhse_reader', 'guard_name' => 'web']);
        $readerRole->givePermissionTo(['personnel.read']);

        /** @var User $collaborator */
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $collaborator->assignRole($readerRole);

        User::factory()->count(2)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);

        $response = $this->actingAs($collaborator, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
    }

    public function test_collaborator_without_personnel_read_cannot_list_users(): void
    {
        $restrictedRole = Role::firstOrCreate(['name' => 'restricted_collaborator', 'guard_name' => 'web']);

        /** @var User $collaborator */
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $collaborator->assignRole($restrictedRole);

        $response = $this->actingAs($collaborator, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'job_title' => 'Responsable Qualité',
            'user_type' => 'company',
            'site_id' => $this->site->id,
            'is_active' => true
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201);

        // Vérifier en base
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'enterprise_id' => $this->enterprise->id
        ]);
    }

    public function test_create_user_sends_verification_email_immediately(): void
    {
        Notification::fake();
        $this->actingAs($this->adminUser, 'sanctum');

        $userData = [
            'first_name' => 'Sarah',
            'last_name' => 'Konan',
            'email' => 'sarah.konan@test.com',
            'job_title' => 'Responsable Qualité',
            'user_type' => 'company',
            'site_id' => $this->site->id,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/users', $userData);
        $response->assertStatus(201);

        $createdUser = User::query()->where('email', 'sarah.konan@test.com')->firstOrFail();

        Notification::assertSentTo($createdUser, VerifyEmailNotification::class);
        $this->assertNull($createdUser->email_verified_at);
    }

    public function test_validates_required_fields()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/users', []);

        $response->assertStatus(422);
        $errors = $response->json('errors');

        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_admin_can_update_user()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company'
        ]);

        $updateData = [
            'first_name' => 'Updated',
            'job_title' => 'New Title'
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated', $user->first_name);
        $this->assertEquals('New Title', $user->job_title);
    }

    public function test_can_toggle_user_status()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true
        ]);

        $response = $this->postJson("/api/v1/users/{$user->id}/toggle-active");

        $response->assertStatus(200);

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_cannot_deactivate_self()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson("/api/v1/users/{$this->adminUser->id}/toggle-active");

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);
    }

    public function test_job_description_collaborators_endpoint()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Créer quelques collaborateurs actifs
        User::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/users/job-description-collaborators');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'email'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_enforces_enterprise_isolation()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Créer un utilisateur dans une autre entreprise
        $otherEnterprise = Enterprise::factory()->create();
        $otherSite = Site::factory()->create(['enterprise_id' => $otherEnterprise->id]);
        $otherUser = User::factory()->create([
            'enterprise_id' => $otherEnterprise->id,
            'site_id' => $otherSite->id,
            'user_type' => 'company'
        ]);

        // Tenter de voir l'utilisateur d'une autre entreprise
        $response = $this->getJson("/api/v1/users/{$otherUser->id}");

        // Devrait être refusé (403) ou non trouvé (404) selon l'implémentation
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_custom_direct_permissions_endpoint_is_disabled()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Permission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);

        $response = $this->postJson("/api/v1/users/{$this->adminUser->id}/assign-custom-permissions", [
            'permissions' => ['documents.read'],
        ]);

        $response->assertStatus(410)
            ->assertJsonFragment([
                'code' => 'RBAC_DIRECT_PERMISSIONS_ENDPOINT_DISABLED',
            ]);
    }

    public function test_roles_only_mode_flag_keeps_endpoint_disabled(): void
    {
        config(['authz.enforce_roles_only_permissions' => true]);

        $this->actingAs($this->adminUser, 'sanctum');
        Permission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);

        $target = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $target->syncRoles(['lecteur']);

        $response = $this->postJson("/api/v1/users/{$target->id}/assign-custom-permissions", [
            'permissions' => ['documents.read'],
        ]);

        $response->assertStatus(410)
            ->assertJsonFragment([
                'code' => 'RBAC_DIRECT_PERMISSIONS_ENDPOINT_DISABLED',
            ]);
    }

    public function test_switching_user_to_admin_entreprise_clears_direct_permissions()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $lecteurRole = Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'documents.read', 'guard_name' => 'web']);
        $lecteurRole->givePermissionTo(['documents.read']);

        $target = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $target->syncRoles(['lecteur']);
        $target->syncPermissions(['documents.read']);
        $this->assertSame(1, $target->getDirectPermissions()->count());

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'access_role' => 'admin_entreprise',
            'permissions' => ['documents.read'],
        ]);

        $response->assertStatus(200);

        $target->refresh();
        $this->assertTrue($target->hasRole('admin_entreprise'));
        $this->assertSame(0, $target->getDirectPermissions()->count());
    }

    public function test_site_manager_cannot_create_user_with_site_manager_or_admin_role(): void
    {
        /** @var User $siteManager */
        $siteManager = User::factory()->createOne([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $siteManager->assignRole('site_manager');

        $this->actingAs($siteManager, 'sanctum');

        $payload = [
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice.martin@test.com',
            'job_title' => 'Responsable Site',
            'user_type' => 'company',
            'site_id' => $this->site->id,
            'access_role' => 'site_manager',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Le rôle 'site_manager' ne peut pas être assigné par un responsable de site.",
            ]);
    }

    public function test_site_manager_cannot_assign_admin_entreprise_role(): void
    {
        /** @var User $siteManager */
        $siteManager = User::factory()->createOne([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $siteManager->assignRole('site_manager');

        $target = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);

        $this->actingAs($siteManager, 'sanctum');

        $response = $this->postJson("/api/v1/users/{$target->id}/assign-role", [
            'role_name' => 'admin_entreprise',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Le rôle 'admin_entreprise' ne peut pas être assigné par un responsable de site.",
            ]);
    }

    public function test_available_roles_excludes_legacy_roles_from_unified_catalog(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Role::firstOrCreate(['name' => 'quality_manager', 'guard_name' => 'web']);

        $response = $this->getJson('/api/v1/available-roles?site_id=' . $this->site->id);

        $response->assertStatus(200);
        $roleNames = collect($response->json('roles'))->pluck('name')->values()->all();

        $this->assertContains('admin_entreprise', $roleNames);
        $this->assertContains('site_manager', $roleNames);
        $this->assertContains('lecteur', $roleNames);
        $this->assertNotContains('quality_manager', $roleNames);
    }

    public function test_super_admin_can_see_enterprise_custom_role_for_requested_site(): void
    {
        $customRoleName = sprintf('custom_enterprise_%d_qhse', $this->enterprise->id);
        Role::query()->updateOrCreate([
            'name' => $customRoleName,
            'guard_name' => 'web',
        ], [
            'enterprise_id' => $this->enterprise->id,
        ]);

        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'user_type' => 'super_admin',
            'enterprise_id' => null,
            'site_id' => null,
        ]);

        $this->actingAs($superAdmin, 'sanctum');

        $response = $this->getJson('/api/v1/available-roles?site_id=' . $this->site->id);

        $response->assertStatus(200);
        $roleNames = collect($response->json('roles'))->pluck('name')->values()->all();

        $this->assertContains($customRoleName, $roleNames);
    }

    public function test_available_roles_hides_legacy_custom_role_without_enterprise_scope(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $legacyCustomRoleName = sprintf('custom_enterprise_%d_legacy', $this->enterprise->id);
        Role::create([
            'name' => $legacyCustomRoleName,
            'guard_name' => 'web',
            'enterprise_id' => null,
        ]);

        $response = $this->getJson('/api/v1/available-roles?site_id=' . $this->site->id);

        $response->assertStatus(200);
        $roleNames = collect($response->json('roles'))->pluck('name')->values()->all();
        $this->assertNotContains($legacyCustomRoleName, $roleNames);
    }

    public function test_assign_role_rejects_legacy_custom_role_without_enterprise_scope(): void
    {
        $legacyCustomRoleName = sprintf('custom_enterprise_%d_legacy_assign', $this->enterprise->id);
        Role::create([
            'name' => $legacyCustomRoleName,
            'guard_name' => 'web',
            'enterprise_id' => null,
        ]);

        $target = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson("/api/v1/users/{$target->id}/assign-role", [
            'role_name' => $legacyCustomRoleName,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Le rôle '{$legacyCustomRoleName}' n'est pas éligible pour les normes actives de ce site.",
            ]);
    }

    public function test_site_manager_creation_requires_enterprise_admin_approval(): void
    {
        Notification::fake();
        $this->actingAs($this->siteManagerUser, 'sanctum');

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Pending',
            'last_name' => 'User',
            'email' => 'pending.user@test.com',
            'job_title' => 'QHSE',
            'user_type' => 'company',
            'site_id' => $this->site->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('notifications.approval_required', true);

        $createdUser = User::query()->where('email', 'pending.user@test.com')->firstOrFail();
        $this->assertSame('pending_admin_approval', $createdUser->collaborator_approval_status);
        $this->assertFalse((bool) $createdUser->is_active);
        $this->assertSame($this->siteManagerUser->id, (int) $createdUser->collaborator_requested_by);
    }

    public function test_admin_can_approve_pending_collaborator(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson("/api/v1/users/{$pendingUser->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.collaborator_approval_status', 'activation_sent');

        $pendingUser->refresh();
        $this->assertSame('activation_sent', $pendingUser->collaborator_approval_status);
        $this->assertTrue((bool) $pendingUser->is_active);
        $this->assertSame($this->adminUser->id, (int) $pendingUser->collaborator_approved_by);
    }

    public function test_enterprise_admin_can_edit_pending_collaborator_without_activating_it(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
            'first_name' => 'Before',
            'job_title' => 'Operateur',
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->patchJson("/api/v1/users/{$pendingUser->id}", [
            'first_name' => 'After',
            'job_title' => 'Responsable QHSE',
        ]);

        $response->assertStatus(200);

        $pendingUser->refresh();
        $this->assertSame('After', $pendingUser->first_name);
        $this->assertSame('Responsable QHSE', $pendingUser->job_title);
        $this->assertSame('pending_admin_approval', $pendingUser->collaborator_approval_status);
        $this->assertFalse((bool) $pendingUser->is_active);
    }

    public function test_enterprise_admin_can_change_pending_collaborator_role_before_approval(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $updateResponse = $this->patchJson("/api/v1/users/{$pendingUser->id}", [
            'access_role' => 'lecteur',
            'job_title' => 'Analyste QHSE',
        ]);

        $updateResponse->assertStatus(200);

        $pendingUser->refresh();
        $this->assertTrue($pendingUser->hasRole('lecteur'));
        $this->assertSame('Analyste QHSE', $pendingUser->job_title);
        $this->assertSame('pending_admin_approval', $pendingUser->collaborator_approval_status);
        $this->assertFalse((bool) $pendingUser->is_active);

        $approveResponse = $this->postJson("/api/v1/users/{$pendingUser->id}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.attributes.collaborator_approval_status', 'activation_sent');

        $pendingUser->refresh();
        $this->assertTrue($pendingUser->hasRole('lecteur'));
        $this->assertSame('activation_sent', $pendingUser->collaborator_approval_status);
        $this->assertTrue((bool) $pendingUser->is_active);
    }

    public function test_pending_collaborator_cannot_be_activated_via_update_endpoint(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->patchJson("/api/v1/users/{$pendingUser->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Impossible d'activer un collaborateur en attente hors workflow d'approbation.",
            ]);

        $pendingUser->refresh();
        $this->assertFalse((bool) $pendingUser->is_active);
        $this->assertSame('pending_admin_approval', $pendingUser->collaborator_approval_status);
    }

    public function test_site_manager_cannot_update_pending_collaborator(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->siteManagerUser, 'sanctum');

        $response = $this->patchJson("/api/v1/users/{$pendingUser->id}", [
            'job_title' => 'Tentative Modification',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => "Seul un admin d'entreprise peut modifier un collaborateur en attente de validation.",
            ]);
    }

    public function test_pending_collaborator_cannot_be_toggled_active_before_approval(): void
    {
        $pendingUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManagerUser->id,
            'collaborator_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson("/api/v1/users/{$pendingUser->id}/toggle-active");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Impossible d'activer un collaborateur en attente hors workflow d'approbation.",
            ]);

        $pendingUser->refresh();
        $this->assertFalse((bool) $pendingUser->is_active);
    }

    public function test_reset_password_marks_activation_sent_collaborator_as_activated(): void
    {
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
            'collaborator_approval_status' => 'activation_sent',
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $collaborator->email],
            [
                'email' => $collaborator->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $collaborator->email,
            'token' => $token,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(200);

        $collaborator->refresh();
        $this->assertSame('activated', $collaborator->collaborator_approval_status);
    }
}
