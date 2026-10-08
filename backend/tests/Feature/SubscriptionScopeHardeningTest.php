<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionScopeHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $adminEntreprise;
    private User $siteManager;
    private User $collaborator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_headquarter' => true,
        ]);

        $this->adminEntreprise = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->adminEntreprise->assignRole('admin_entreprise');

        $this->siteManager = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->siteManager->assignRole('site_manager');

        $this->collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->collaborator->assignRole('lecteur');
    }

    public function test_sensitive_access_catalog_endpoint_is_blocked_without_active_subscription_for_all_company_profiles(): void
    {
        foreach ([$this->adminEntreprise, $this->siteManager, $this->collaborator] as $actor) {
            $response = $this->actingAs($actor, 'sanctum')->getJson('/api/v1/access/catalog');

            $response->assertStatus(423)
                ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED_FOR_SITE')
                ->assertJsonPath('redirect', '/company/subscription');
        }
    }

    public function test_users_endpoint_is_no_longer_bypassed_when_subscription_is_missing(): void
    {
        $response = $this->actingAs($this->adminEntreprise, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(423)
            ->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED_FOR_SITE');
    }

    public function test_subscription_status_remains_reachable_without_active_subscription_for_all_company_profiles(): void
    {
        foreach ([$this->adminEntreprise, $this->siteManager, $this->collaborator] as $actor) {
            $response = $this->actingAs($actor, 'sanctum')
                ->getJson('/api/v1/subscription/status');

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('site_id', $this->site->id);
        }
    }
}

