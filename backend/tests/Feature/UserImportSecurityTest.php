<?php

namespace Tests\Feature;

use App\Jobs\ImportUsersJob;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Module;
use App\Models\Offer;
use App\Models\Site;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $this->adminUser->assignRole('admin_entreprise');
    }

    public function test_import_request_rejects_unknown_permission_names(): void
    {
        Queue::fake();

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/users/import', [
            'file' => UploadedFile::fake()->create('users.csv', 1, 'text/csv'),
            'site_id' => $this->site->id,
            'permissions' => ['permission.inconnue'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['permissions.0']);

        Queue::assertNothingPushed();
    }

    public function test_import_permissions_are_scoped_to_active_site_catalog_before_job_dispatch(): void
    {
        Queue::fake();

        Permission::firstOrCreate(['name' => 'personnel.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'personnel.audit.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'personnel.audit.docs.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'finance.read', 'guard_name' => 'web']);

        $offer = Offer::factory()->create();
        EnterpriseSubscription::factory()->create([
            'offer_id' => $offer->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'status' => 'active',
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
        ]);

        $module = Module::query()->create([
            'name' => 'Personnel',
            'code' => 'personnel',
            'iso_point' => 5,
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $subModule = SubModule::query()->create([
            'module_id' => $module->id,
            'name' => 'Audit',
            'code' => 'audit',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        SubModuleSection::query()->create([
            'sub_module_id' => $subModule->id,
            'name' => 'Documents',
            'code' => 'docs',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/users/import', [
            'file' => UploadedFile::fake()->create('users.csv', 1, 'text/csv'),
            'site_id' => $this->site->id,
            'permissions' => [
                'personnel.read',
                'personnel.audit.read',
                'personnel.audit.docs.read',
                'finance.read',
            ],
        ]);

        $response->assertStatus(202);

        Queue::assertPushed(ImportUsersJob::class, function (ImportUsersJob $job): bool {
            $permissions = $this->readPrivateProperty($job, 'permissions');

            return $permissions === [
                'personnel.read',
                'personnel.audit.read',
                'personnel.audit.docs.read',
            ];
        });
    }

    public function test_import_with_admin_entreprise_default_role_strips_direct_permissions(): void
    {
        Queue::fake();

        Permission::firstOrCreate(['name' => 'personnel.read', 'guard_name' => 'web']);

        $offer = Offer::factory()->create();
        EnterpriseSubscription::factory()->create([
            'offer_id' => $offer->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'status' => 'active',
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
        ]);

        Module::query()->create([
            'name' => 'Personnel',
            'code' => 'personnel',
            'iso_point' => 5,
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/users/import', [
            'file' => UploadedFile::fake()->create('users.csv', 1, 'text/csv'),
            'site_id' => $this->site->id,
            'default_role' => 'admin_entreprise',
            'permissions' => ['personnel.read'],
        ]);

        $response->assertStatus(202);

        Queue::assertPushed(ImportUsersJob::class, function (ImportUsersJob $job): bool {
            $permissions = $this->readPrivateProperty($job, 'permissions');
            $defaultRole = $this->readPrivateProperty($job, 'defaultRole');

            return $defaultRole === 'admin_entreprise' && $permissions === [];
        });
    }

    public function test_import_with_legacy_default_role_falls_back_to_lecteur(): void
    {
        Queue::fake();

        Role::firstOrCreate(['name' => 'quality_manager', 'guard_name' => 'web']);

        $offer = Offer::factory()->create();
        EnterpriseSubscription::factory()->create([
            'offer_id' => $offer->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'status' => 'active',
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
        ]);

        Module::query()->create([
            'name' => 'Personnel',
            'code' => 'personnel',
            'iso_point' => 5,
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/users/import', [
            'file' => UploadedFile::fake()->create('users.csv', 1, 'text/csv'),
            'site_id' => $this->site->id,
            'default_role' => 'quality_manager',
        ]);

        $response->assertStatus(202);

        Queue::assertPushed(ImportUsersJob::class, function (ImportUsersJob $job): bool {
            $defaultRole = $this->readPrivateProperty($job, 'defaultRole');

            return $defaultRole === 'lecteur';
        });
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        $reader = function () use ($property) {
            return $this->{$property};
        };

        return $reader->call($object);
    }
}
