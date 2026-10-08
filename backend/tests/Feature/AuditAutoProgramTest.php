<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\AuditProgram;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditAutoProgramTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;

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
        $role->givePermissionTo(['audits.create']);

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);
    }

    public function test_internal_audit_creation_auto_creates_program_when_missing(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/audits', [
                'site_id' => $this->site->id,
                'type' => 'internal',
                'title' => 'Audit interne Q3',
                'planned_date' => '2030-09-10',
                'lead_auditor_id' => $this->admin->id,
                'assigned_to' => $this->admin->id,
                'objectives' => 'Vérifier conformité',
                'reference_documents' => 'ISO 9001',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('audit_programs', [
            'site_id' => $this->site->id,
            'year' => 2030,
        ]);

        $auditProgram = AuditProgram::query()
            ->where('site_id', $this->site->id)
            ->where('year', 2030)
            ->first();

        $this->assertNotNull($auditProgram);
        $this->assertSame($auditProgram->id, (int) $response->json('audit_program_id'));
    }
}

