<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\SecurityAuditLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterpriseA;
    private Site $siteA1;
    private Site $siteA2;
    private Enterprise $enterpriseB;
    private Site $siteB1;
    private User $adminEntrepriseA;
    private User $siteManagerA1;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $securityAuditRead = Permission::firstOrCreate([
            'name' => 'security_audit.read',
            'guard_name' => 'web',
        ]);

        $this->enterpriseA = Enterprise::factory()->create();
        $this->siteA1 = Site::factory()->create(['enterprise_id' => $this->enterpriseA->id]);
        $this->siteA2 = Site::factory()->create(['enterprise_id' => $this->enterpriseA->id]);

        $this->enterpriseB = Enterprise::factory()->create();
        $this->siteB1 = Site::factory()->create(['enterprise_id' => $this->enterpriseB->id]);

        $this->adminEntrepriseA = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
        ]);
        $this->adminEntrepriseA->assignRole('admin_entreprise');
        $this->adminEntrepriseA->givePermissionTo($securityAuditRead);

        $this->siteManagerA1 = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
        ]);
        $this->siteManagerA1->assignRole('site_manager');
        $this->siteManagerA1->givePermissionTo($securityAuditRead);

        $this->superAdmin = User::factory()->create([
            'user_type' => 'super_admin',
            'enterprise_id' => null,
            'site_id' => null,
        ]);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdmin->givePermissionTo($securityAuditRead);
    }

    public function test_admin_entreprise_can_only_read_own_enterprise_logs(): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => $this->adminEntrepriseA->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
            'event_type' => 'user_management',
            'action' => 'collaborator_creation_approved',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'high',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => $this->siteManagerA1->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA2->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'medium',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseB->id,
            'site_id' => $this->siteB1->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'critical',
            'status' => 'logged',
        ]);

        $response = $this->actingAs($this->adminEntrepriseA, 'sanctum')
            ->getJson('/api/v1/security-audit-logs');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertTrue(collect($data)->every(
            fn(array $log) => (int) ($log['enterprise_id'] ?? 0) === $this->enterpriseA->id
        ));
    }

    public function test_site_manager_is_restricted_to_own_site_logs(): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => $this->siteManagerA1->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'high',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => $this->adminEntrepriseA->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA2->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'high',
            'status' => 'logged',
        ]);

        $response = $this->actingAs($this->siteManagerA1, 'sanctum')
            ->getJson('/api/v1/security-audit-logs');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($this->siteA1->id, (int) ($data[0]['site_id'] ?? 0));
    }

    public function test_super_admin_can_filter_stats_by_enterprise(): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'critical',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseB->id,
            'site_id' => $this->siteB1->id,
            'event_type' => 'api_access',
            'action' => 'post_failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'risk_level' => 'critical',
            'status' => 'logged',
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/security-audit-logs/stats?enterprise_id=' . $this->enterpriseA->id);

        $response->assertOk()
            ->assertJsonPath('total_events', 1)
            ->assertJsonPath('critical_events', 1)
            ->assertJsonPath('failed_logins_today', 1);
    }

    public function test_shadow_rbac_summary_is_scoped_for_admin_entreprise(): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => $this->adminEntrepriseA->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
            'event_type' => 'authorization',
            'action' => 'rbac_shadow_divergence',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [
                'reason' => 'permission',
                'requested_permission' => 'performance.read',
            ],
            'risk_level' => 'high',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => $this->siteManagerA1->id,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA2->id,
            'event_type' => 'authorization',
            'action' => 'rbac_shadow_divergence',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [
                'reason' => 'scope',
                'requested_permission' => 'actions.read',
            ],
            'risk_level' => 'medium',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseB->id,
            'site_id' => $this->siteB1->id,
            'event_type' => 'authorization',
            'action' => 'rbac_shadow_divergence',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [
                'reason' => 'subscription',
                'requested_permission' => 'actions.read',
            ],
            'risk_level' => 'medium',
            'status' => 'logged',
        ]);

        $response = $this->actingAs($this->adminEntrepriseA, 'sanctum')
            ->getJson('/api/v1/security-audit-logs/shadow-rbac-summary?window_days=30');

        $response->assertOk()
            ->assertJsonPath('total_divergences', 2)
            ->assertJsonPath('by_reason.permission', 1)
            ->assertJsonPath('by_reason.scope', 1)
            ->assertJsonPath('by_reason.subscription', 0);
    }

    public function test_super_admin_can_filter_shadow_rbac_summary_by_enterprise(): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseA->id,
            'site_id' => $this->siteA1->id,
            'event_type' => 'authorization',
            'action' => 'rbac_shadow_divergence',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [
                'reason' => 'permission',
                'requested_permission' => 'performance.read',
            ],
            'risk_level' => 'high',
            'status' => 'logged',
        ]);

        SecurityAuditLog::query()->create([
            'user_id' => null,
            'enterprise_id' => $this->enterpriseB->id,
            'site_id' => $this->siteB1->id,
            'event_type' => 'authorization',
            'action' => 'rbac_shadow_divergence',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [
                'reason' => 'subscription',
                'requested_permission' => 'actions.read',
            ],
            'risk_level' => 'medium',
            'status' => 'logged',
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/security-audit-logs/shadow-rbac-summary?window_days=30&enterprise_id=' . $this->enterpriseA->id);

        $response->assertOk()
            ->assertJsonPath('total_divergences', 1)
            ->assertJsonPath('by_reason.permission', 1)
            ->assertJsonPath('by_reason.subscription', 0)
            ->assertJsonPath('top_permissions.0.permission', 'performance.read')
            ->assertJsonPath('top_permissions.0.count', 1);
    }
}
