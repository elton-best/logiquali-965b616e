<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Audit;
use App\Models\AuditProgram;
use App\Models\Enterprise;
use App\Models\EvaluationCriteria;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditAuditorEvaluationRequestTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;
    private User $auditor;

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
        Permission::firstOrCreate(['name' => 'audits.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'audits.create', 'guard_name' => 'web']);
        $role->givePermissionTo(['audits.update', 'audits.create']);

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
            'email' => 'auditor@example.test',
        ]);
    }

    public function test_create_auditor_evaluation_request_from_audit(): void
    {
        $program = AuditProgram::query()->create([
            'site_id' => $this->site->id,
            'year' => 2030,
            'program_manager_id' => $this->admin->id,
            'status' => 'draft',
            'title' => 'Programme audit test',
        ]);

        $auditPayload = [
            'ref' => 'AUD-2030-001',
            'audit_program_id' => $program->id,
            'site_id' => $this->site->id,
            'type' => 'internal',
            'title' => 'Audit interne',
            'planned_date' => '2030-06-01',
            'lead_auditor_id' => $this->admin->id,
            'assigned_to' => $this->admin->id,
            'status' => 'planned',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('audits', 'enterprise_id')) {
            $auditPayload['enterprise_id'] = $this->enterprise->id;
        }
        $audit = Audit::query()->create($auditPayload);

        $criteria = EvaluationCriteria::query()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'name' => 'Maîtrise technique',
            'code' => 'AU-X1',
            'category' => 'Compétences',
            'form_type' => 'evaluation_auditeur',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/audits/{$audit->id}/auditor-evaluations", [
                'auditor_user_id' => $this->auditor->id,
                'criteria_ids' => [$criteria->id],
                'send_immediately' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'evaluation_auditeur')
            ->assertJsonPath('data.requestable_type', Audit::class)
            ->assertJsonPath('data.requestable_id', $audit->id)
            ->assertJsonPath('data.recipient_email', 'auditor@example.test')
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('evaluation_requests', [
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'type' => 'evaluation_auditeur',
            'requestable_type' => Audit::class,
            'requestable_id' => $audit->id,
            'recipient_email' => 'auditor@example.test',
            'status' => 'sent',
        ]);
    }
}
