<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RbacPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);

        Permission::findOrCreate('verify_documents', 'web');
        Permission::findOrCreate('approve_documents', 'web');
    }

    public function test_sidebar_exposes_workflow_permissions_for_authorized_user(): void
    {
        $user = $this->makeUserWithWorkflowPermissions();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users/sidebar');

        $response->assertOk();
        $response->assertJsonFragment(['verify_documents']);
        $response->assertJsonFragment(['approve_documents']);
        $response->assertJsonFragment(['key' => 'verification']);
        $response->assertJsonFragment(['key' => 'approbation']);
    }

    public function test_sidebar_hides_workflow_permissions_for_user_without_rights(): void
    {
        $user = $this->makeBaseUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users/sidebar');

        $response->assertOk();
        $response->assertJsonMissing(['key' => 'verification']);
        $response->assertJsonMissing(['key' => 'approbation']);
    }

    public function test_can_assign_direct_permissions_to_user(): void
    {
        $admin = $this->makeBaseUser();
        \Spatie\Permission\Models\Role::findOrCreate('admin_entreprise', 'web');
        $admin->assignRole('admin_entreprise');

        $user = User::factory()->create([
            'enterprise_id' => $admin->enterprise_id,
            'site_id' => $admin->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/users/{$user->id}/assign-custom-permissions",
            ['permissions' => ['verify_documents']],
        );

        $response->assertStatus(410)
            ->assertJsonPath('code', 'RBAC_DIRECT_PERMISSIONS_ENDPOINT_DISABLED');

        $this->assertFalse($user->fresh()->hasDirectPermission('verify_documents'));
    }

    public function test_cannot_assign_direct_permissions_to_admin_entreprise(): void
    {
        $admin = $this->makeBaseUser();
        \Spatie\Permission\Models\Role::findOrCreate('admin_entreprise', 'web');
        $admin->assignRole('admin_entreprise');

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/users/{$admin->id}/assign-custom-permissions",
            ['permissions' => ['verify_documents']],
        );

        $response->assertStatus(410)
            ->assertJsonPath('code', 'RBAC_DIRECT_PERMISSIONS_ENDPOINT_DISABLED');
    }

    private function makeBaseUser(): User
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $offer = Offer::factory()->create(['is_active' => true]);
        $norm = Norm::factory()->create([
            'status' => 'published',
            'domain' => 'quality',
        ]);
        $offer->norms()->syncWithoutDetaching([$norm->id]);

        EnterpriseSubscription::factory()->create([
            'site_id' => $site->id,
            'offer_id' => $offer->id,
            'start_date' => now()->subDays(2),
            'expiration_date' => now()->addMonths(3),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
        ]);

        return User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }

    private function makeUserWithWorkflowPermissions(): User
    {
        $user = $this->makeBaseUser();
        $user->givePermissionTo(['verify_documents', 'approve_documents']);

        return $user;
    }
}
