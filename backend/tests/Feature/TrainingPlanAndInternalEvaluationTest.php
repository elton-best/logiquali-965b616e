<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Formation;
use App\Models\Plan;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingPlanAndInternalEvaluationTest extends TestCase
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
        foreach ([
            'formations.read',
            'formations.create',
            'formations.update',
            'formations.delete',
            'training_plans.read',
            'training_plans.create',
            'training_plans.update',
            'training_plans.delete',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
        $role->givePermissionTo([
            'formations.read',
            'formations.create',
            'formations.update',
            'formations.delete',
            'training_plans.read',
            'training_plans.create',
            'training_plans.update',
            'training_plans.delete',
        ]);

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);
    }

    public function test_training_plan_returns_aggregated_metrics_from_formations(): void
    {
        Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'realisee',
            'cout' => 100000,
            'date_debut' => '2026-02-02',
            'date_fin' => '2026-02-05',
        ]);
        Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'cout' => 50000,
            'date_debut' => '2026-06-01',
            'date_fin' => '2026-06-03',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/training-plans', [
                'year' => 2026,
                'status' => 'active',
                'total_budget' => 300000,
                'site_id' => $this->site->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('year', 2026);
        $response->assertJsonPath('stats.total_formations', 2);
        $response->assertJsonPath('stats.realisees', 1);
        $response->assertJsonPath('spent_amount', 100000);
    }

    public function test_internal_evaluation_requires_formation_realisee(): void
    {
        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-03',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/internal-evaluation', [
                'method' => 'combinaison',
                'scores' => [
                    'pedagogie' => 4,
                    'contenu' => 5,
                    'applicabilite' => 4,
                    'animation' => 5,
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_internal_evaluation_upsert_computes_global_score(): void
    {
        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'realisee',
            'date_debut' => '2026-04-01',
            'date_fin' => '2026-04-03',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/internal-evaluation', [
                'method' => 'combinaison',
                'scores' => [
                    'pedagogie' => 4,
                    'contenu' => 5,
                    'applicabilite' => 3,
                    'animation' => 4,
                ],
                'strengths' => ['Cas pratiques'],
                'improvements' => ['Plus de temps atelier'],
                'comment' => 'Evaluation interne formation QHSE',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('global_score', 4);
        $response->assertJsonPath('method', 'combinaison');
        $response->assertJsonPath('scores.pedagogie', 4);
    }

    public function test_deprecated_generic_training_plan_route_is_rejected(): void
    {
        Plan::create([
            'site_id' => $this->site->id,
            'type' => 'training',
            'title' => 'Ancien plan formation',
            'year' => 2026,
            'content' => ['note' => 'legacy'],
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/plans?type=training&site_id=' . $this->site->id);

        $response->assertStatus(410);
    }

    public function test_training_plan_export_xlsx_returns_file_download(): void
    {
        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'realisee',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-03',
            'cout' => 120000,
        ]);

        $planResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/training-plans', [
                'year' => 2026,
                'status' => 'active',
                'site_id' => $this->site->id,
            ]);

        $planResponse->assertStatus(201);
        $planId = $planResponse->json('id');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/training-plans/' . $planId . '/export-xlsx');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $this->assertNotNull($formation->id);
    }
}
