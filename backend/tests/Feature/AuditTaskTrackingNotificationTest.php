<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditTaskTrackingNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;
    private User $auditor;
    private User $auditee;

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

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        $role = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'audits.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'audits.update', 'guard_name' => 'web']);
        $role->givePermissionTo(['audits.create', 'audits.update']);

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);

        $this->auditor = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $this->auditee = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }

    public function test_audit_workflow_syncs_task_tracking_and_notifies_stakeholders(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'type' => 'internal',
            'title' => 'Audit workflow E5',
            'planned_date' => '2030-09-10',
            'lead_auditor_id' => $this->admin->id,
            'assigned_to' => $this->admin->id,
            'objectives' => 'Vérifier conformité',
            'reference_documents' => 'ISO 9001',
            'auditor_ids' => [$this->auditor->id],
            'auditee_ids' => [$this->auditee->id],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/audits', $payload);

        $response->assertStatus(201);
        $auditId = (int) $response->json('id');

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->admin->id,
            'trackable_type' => \App\Models\Audit::class,
            'trackable_id' => $auditId,
            'status' => 'non_demarre',
            'progress_rate' => 0,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->auditor->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->auditee->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/audits/{$auditId}/start", [
                'actual_date' => '2030-09-11',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->admin->id,
            'trackable_type' => \App\Models\Audit::class,
            'trackable_id' => $auditId,
            'status' => 'en_cours',
            'progress_rate' => 50,
        ]);

        if (Schema::hasColumn('task_tracking', 'deleted_at')) {
            $this->assertDatabaseMissing('task_tracking', [
                'user_id' => $this->admin->id,
                'trackable_type' => \App\Models\Audit::class,
                'trackable_id' => $auditId,
                'status' => 'non_demarre',
                'progress_rate' => 0,
            ]);
        }
    }
}

