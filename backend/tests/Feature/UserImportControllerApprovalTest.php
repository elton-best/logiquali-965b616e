<?php

namespace Tests\Feature;

use App\Jobs\ImportUsersJob;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use App\Notifications\User\PendingImportApprovalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use ReflectionObject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImportControllerApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $siteManager;
    private User $enterpriseAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate import approval workflow from global blocking middleware (423).
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);

        Permission::findOrCreate('personnel.create', 'web');

        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'web']);

        $this->siteManager = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
        ]);
        $this->siteManager->assignRole('site_manager');
        $this->siteManager->givePermissionTo('personnel.create');

        $this->enterpriseAdmin = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
        ]);
        $this->enterpriseAdmin->assignRole('admin_entreprise');
        $this->enterpriseAdmin->givePermissionTo('personnel.create');
    }

    public function test_site_manager_import_requires_admin_approval_before_emails(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "first_name,last_name,email,job_title\nJane,Doe,jane.import@test.com,Responsable QHSE"
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

    public function test_enterprise_admin_import_does_not_require_manual_approval(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "first_name,last_name,email,job_title\nJohn,Doe,john.import@test.com,Responsable Qualite"
        );

        $response = $this->actingAs($this->enterpriseAdmin, 'sanctum')
            ->post('/api/v1/users/import', [
                'file' => $file,
                'site_id' => $this->site->id,
                'default_role' => 'lecteur',
                'send_welcome_email' => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('notifications.approval_required', false);

        Queue::assertPushed(ImportUsersJob::class);
    }

    public function test_import_rejects_legacy_custom_default_role_without_enterprise_scope(): void
    {
        Queue::fake();

        $legacyCustomRoleName = sprintf('custom_enterprise_%d_legacy_import', $this->enterprise->id);
        Role::create([
            'name' => $legacyCustomRoleName,
            'guard_name' => 'web',
            'enterprise_id' => null,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "first_name,last_name,email,job_title\nJane,Doe,jane.legacy-role@test.com,Responsable QHSE"
        );

        $response = $this->actingAs($this->siteManager, 'sanctum')
            ->post('/api/v1/users/import', [
                'file' => $file,
                'site_id' => $this->site->id,
                'default_role' => $legacyCustomRoleName,
                'send_welcome_email' => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('notifications.approval_required', true)
            ->assertJsonPath('notifications.activation_email_sent', false);

        Queue::assertPushed(ImportUsersJob::class, function (ImportUsersJob $job): bool {
            return $this->readPrivateProperty($job, 'defaultRole') === 'lecteur';
        });
    }

    public function test_import_job_notifies_enterprise_admin_when_pending_approvals_are_created(): void
    {
        Notification::fake();
        Storage::fake('local');

        $path = 'imports/users/pending-import.csv';
        Storage::disk('local')->put(
            $path,
            "first_name,last_name,email,job_title\nAlice,Pending,alice.pending@test.com,Responsable QHSE\n"
        );

        $job = new ImportUsersJob(
            path: $path,
            enterpriseId: (int) $this->enterprise->id,
            siteId: (int) $this->site->id,
            permissions: [],
            sendWelcomeEmail: false,
            defaultRole: 'lecteur',
            restrictPrivilegedRoles: true,
            createdByUserId: (int) $this->siteManager->id,
            requiresManualApproval: true
        );

        $job->handle();

        Notification::assertSentTo(
            [$this->enterpriseAdmin],
            PendingImportApprovalNotification::class,
            function (PendingImportApprovalNotification $notification, array $channels): bool {
                $payload = $notification->toArray($this->enterpriseAdmin);

                return in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && ($payload['data']['pending_approval_users'] ?? 0) === 1
                    && ($payload['data']['site_id'] ?? 0) === (int) $this->site->id;
            }
        );
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionObject($object);
        $reflectionProperty = $reflection->getProperty($property);

        return $reflectionProperty->getValue($object);
    }
}
