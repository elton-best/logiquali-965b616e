<?php

namespace Tests\Feature;

use App\Jobs\ImportUsersJob;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionAccessWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $enterpriseAdmin;
    private User $siteManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enterprise = Enterprise::factory()->approved()->create();
        $this->enterprise->forceFill([
            'status' => 'active',
            'approval_status' => 'approved',
            'field' => 'Industrie',
            'domaine_activite_set' => true,
        ])->save();

        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_headquarter' => true,
            'is_active' => true,
        ]);

        $this->seedActiveSubscriptionCoverageForSite($this->site);

        Permission::findOrCreate('personnel.create', 'web');
        Permission::findOrCreate('personnel.read', 'web');
        Permission::findOrCreate('roles.create', 'web');
        Permission::findOrCreate('roles.read', 'web');

        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $this->enterpriseAdmin = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->enterpriseAdmin->assignRole('admin_entreprise');
        $this->enterpriseAdmin->givePermissionTo(['personnel.create', 'personnel.read', 'roles.create', 'roles.read']);

        $this->siteManager = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->siteManager->assignRole('site_manager');
        $this->siteManager->givePermissionTo(['personnel.create', 'personnel.read']);
    }

    public function test_pending_collaborator_is_blocked_at_login_even_if_is_active_true(): void
    {
        $pendingUser = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'email' => 'pending.login@test.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
            'collaborator_approval_status' => 'pending_admin_approval',
        ]);
        $pendingUser->assignRole('lecteur');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pending.login@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'pending_admin_approval');
    }

    public function test_site_manager_create_user_flow_goes_to_pending_with_middlewares_enabled(): void
    {
        $response = $this->actingAs($this->siteManager, 'sanctum')
            ->postJson('/api/v1/users', [
                'first_name' => 'Flow',
                'last_name' => 'Creator',
                'email' => 'flow.creator@test.com',
                'job_title' => 'Responsable QHSE',
                'site_id' => $this->site->id,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('notifications.approval_required', true)
            ->assertJsonPath('notifications.activation_email_sent', false)
            ->assertJsonPath('data.attributes.collaborator_approval_status', 'pending_admin_approval')
            ->assertJsonPath('data.attributes.is_active', false);
    }

    public function test_site_manager_import_flow_requires_admin_approval_with_middlewares_enabled(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "first_name,last_name,email,job_title\nIntegration,Import,integration.import@test.com,Responsable Qualite"
        );

        $response = $this->actingAs($this->siteManager, 'sanctum')
            ->post('/api/v1/users/import', [
                'file' => $file,
                'site_id' => $this->site->id,
                'default_role' => 'lecteur',
                'send_welcome_email' => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('notifications.approval_required', true)
            ->assertJsonPath('notifications.activation_email_sent', false);

        Queue::assertPushed(ImportUsersJob::class);
    }

    public function test_admin_can_approve_pending_collaborator_with_middlewares_enabled(): void
    {
        $pendingUser = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'is_active' => false,
            'collaborator_approval_status' => 'pending_admin_approval',
            'collaborator_requested_by' => $this->siteManager->id,
            'collaborator_requested_at' => now(),
        ]);
        $pendingUser->assignRole('lecteur');

        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->postJson('/api/v1/users/' . $pendingUser->id . '/approve');

        $response->assertOk()
            ->assertJsonPath('data.attributes.collaborator_approval_status', 'activation_sent');

        $pendingUser->refresh();
        $this->assertTrue((bool) $pendingUser->is_active);
        $this->assertSame($this->enterpriseAdmin->id, (int) $pendingUser->collaborator_approved_by);
    }

    public function test_access_catalog_endpoint_remains_accessible_with_valid_subscription_context(): void
    {
        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    private function seedActiveSubscriptionCoverageForSite(Site $site): void
    {
        $norm = Norm::factory()->create([
            'code' => 'ISO-9001',
            'name' => 'ISO 9001',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $offer = Offer::factory()->create([
            'name' => 'Pack Integration',
            'is_active' => true,
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
    }
}

