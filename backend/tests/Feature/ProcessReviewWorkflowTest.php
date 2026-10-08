<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcessReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
        ]);
    }

    public function test_current_review_can_be_updated_and_closed_definitively(): void
    {
        [$user, $process] = $this->makeUserAndProcess('admin_entreprise');
        $user->givePermissionTo(['process_reviews.read', 'process_reviews.update']);

        $currentResponse = $this->actingAs($user)
            ->getJson("/api/v1/processes/{$process->id}/reviews/current");

        $currentResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'planned');

        $updateResponse = $this->actingAs($user)
            ->putJson("/api/v1/processes/{$process->id}/reviews/current", [
                'sections' => [
                    'pip_summary' => 'Revue en cours',
                ],
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.sections.pip_summary', 'Revue en cours');

        $closeResponse = $this->actingAs($user)
            ->postJson("/api/v1/processes/{$process->id}/reviews/current/close");

        $closeResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $lockedUpdate = $this->actingAs($user)
            ->putJson("/api/v1/processes/{$process->id}/reviews/current", [
                'sections' => [
                    'pip_summary' => 'Tentative après clôture',
                ],
            ]);

        $lockedUpdate->assertStatus(422);
    }

    public function test_identification_update_is_restricted_to_rq_profile(): void
    {
        [$adminUser, $process] = $this->makeUserAndProcess('admin_entreprise');
        $adminUser->givePermissionTo(['process_reviews.read', 'process_reviews.update']);

        /** @var User $nonRqUser */
        $nonRqUser = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $process->site_id,
            'role' => 'collaborateur',
        ]);
        $nonRqUser->givePermissionTo(['process_reviews.read', 'process_reviews.update']);
        $process->update(['copilot_id' => $nonRqUser->id]);

        $forbiddenResponse = $this->actingAs($nonRqUser)
            ->putJson("/api/v1/processes/{$process->id}/reviews/current", [
                'identification' => [
                    'rq_name' => 'Utilisateur non RQ',
                ],
            ]);

        $forbiddenResponse->assertForbidden();
    }

    public function test_read_only_user_gets_capabilities_without_update_right(): void
    {
        [$readOnlyUser, $process] = $this->makeUserAndProcess('collaborateur');
        $readOnlyUser->givePermissionTo(['process_reviews.read']);

        $response = $this->actingAs($readOnlyUser)
            ->getJson("/api/v1/processes/{$process->id}/reviews/current");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.capabilities.can_read_review', true)
            ->assertJsonPath('data.capabilities.can_update_review', false)
            ->assertJsonPath('data.capabilities.can_edit_identification', false);
    }

    public function test_legacy_review_endpoints_are_not_available_anymore(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => [
                'uri' => ltrim((string) $route->uri(), '/'),
                'methods' => $route->methods(),
            ]);

        $legacyUris = [
            'api/v1/processes/{process}/reviews',
            'api/v1/processes/{process}/reviews/{review}',
            'api/v1/processes/{process}/reviews/{review}/close',
        ];

        foreach ($legacyUris as $uri) {
            $match = $routes->first(fn ($route) => $route['uri'] === $uri);
            $this->assertNull($match, "Legacy route still registered: {$uri}");
        }
    }

    public function test_linked_data_endpoint_is_scoped_to_current_tenant_and_site(): void
    {
        [$user, $process] = $this->makeUserAndProcess('admin_entreprise');
        $user->givePermissionTo(['process_reviews.read']);

        $sameEnterpriseOtherSite = Site::factory()->create([
            'enterprise_id' => $process->enterprise_id,
        ]);

        $otherSite = Site::factory()->create();
        $otherProcess = Process::factory()->create([
            'site_id' => $otherSite->id,
            'enterprise_id' => $otherSite->enterprise_id,
            'pilot_id' => $user->id,
            'copilot_id' => null,
            'category' => 'support',
        ]);

        $duerpInScopeId = DB::table('duerp')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $process->site_id,
            'version' => '1.0',
            'is_current' => true,
            'evaluation_date' => now()->toDateString(),
            'next_evaluation_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $duerpOtherSiteId = DB::table('duerp')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $sameEnterpriseOtherSite->id,
            'version' => '1.0',
            'is_current' => true,
            'evaluation_date' => now()->toDateString(),
            'next_evaluation_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $duerpOtherEnterpriseId = DB::table('duerp')->insertGetId([
            'enterprise_id' => $otherSite->enterprise_id,
            'site_id' => $otherSite->id,
            'version' => '1.0',
            'is_current' => true,
            'evaluation_date' => now()->toDateString(),
            'next_evaluation_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inScopeDangerId = DB::table('duerp_dangers')->insertGetId([
            'duerp_id' => $duerpInScopeId,
            'process_id' => $process->id,
            'danger_type' => 'chute',
            'danger_description' => 'Risque de chute',
            'criticality_score' => 12,
            'criticality_level' => 'unacceptable',
            'actions' => json_encode([[
                'status' => 'in_progress',
                'deadline' => now()->subDay()->toDateString(),
            ]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherSiteDangerId = DB::table('duerp_dangers')->insertGetId([
            'duerp_id' => $duerpOtherSiteId,
            'process_id' => null,
            'danger_type' => 'incendie',
            'danger_description' => 'Risque incendie',
            'criticality_score' => 20,
            'criticality_level' => 'unacceptable',
            'actions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherEnterpriseDangerId = DB::table('duerp_dangers')->insertGetId([
            'duerp_id' => $duerpOtherEnterpriseId,
            'process_id' => null,
            'danger_type' => 'chimique',
            'danger_description' => 'Risque chimique',
            'criticality_score' => 20,
            'criticality_level' => 'unacceptable',
            'actions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inScopeAesId = DB::table('aspects_environnementaux')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $process->site_id,
            'process_id' => $process->id,
            'type' => 'dechet',
            'designation' => 'Déchets dangereux',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 4,
            'detectabilite' => 3,
            'aspect_significatif' => true,
            'objectifs_amelioration' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherSiteAesId = DB::table('aspects_environnementaux')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $sameEnterpriseOtherSite->id,
            'process_id' => null,
            'type' => 'dechet',
            'designation' => 'Déchets autre site',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 4,
            'detectabilite' => 3,
            'aspect_significatif' => true,
            'objectifs_amelioration' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherEnterpriseAesId = DB::table('aspects_environnementaux')->insertGetId([
            'enterprise_id' => $otherSite->enterprise_id,
            'site_id' => $otherSite->id,
            'process_id' => $otherProcess->id,
            'type' => 'dechet',
            'designation' => 'Déchets autre entreprise',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 4,
            'detectabilite' => 3,
            'aspect_significatif' => true,
            'objectifs_amelioration' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/processes/{$process->id}/reviews/current/linked-data");

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $duerpIds = collect($response->json('data.duerp_top'))->pluck('id')->all();
        $aesIds = collect($response->json('data.aes_top'))->pluck('id')->all();
        $duerpOverdueActions = $response->json('data.summary.duerp_overdue_actions');
        $aesMissingActions = $response->json('data.summary.aes_missing_actions');

        $this->assertIsArray($duerpIds);
        $this->assertLessThanOrEqual(3, count($duerpIds));
        $this->assertNotContains($otherSiteDangerId, $duerpIds);
        $this->assertNotContains($otherEnterpriseDangerId, $duerpIds);

        $this->assertIsArray($aesIds);
        $this->assertLessThanOrEqual(3, count($aesIds));
        $this->assertNotContains($otherSiteAesId, $aesIds);
        $this->assertNotContains($otherEnterpriseAesId, $aesIds);
        $this->assertIsNumeric($duerpOverdueActions);
        $this->assertGreaterThanOrEqual(0, (int) $duerpOverdueActions);
        $this->assertIsNumeric($aesMissingActions);
        $this->assertGreaterThanOrEqual(0, (int) $aesMissingActions);
    }

    public function test_can_create_linked_action_from_duerp_row_with_traceability(): void
    {
        [$user, $process] = $this->makeUserAndProcess('admin_entreprise');
        $user->givePermissionTo(['process_reviews.read', 'process_reviews.update']);

        $duerpId = DB::table('duerp')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $process->site_id,
            'version' => '1.0',
            'is_current' => true,
            'evaluation_date' => now()->toDateString(),
            'next_evaluation_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dangerId = DB::table('duerp_dangers')->insertGetId([
            'duerp_id' => $duerpId,
            'process_id' => $process->id,
            'danger_type' => 'incendie',
            'danger_description' => 'Produit inflammable',
            'criticality_score' => 15,
            'criticality_level' => 'unacceptable',
            'actions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ensure current review exists and capture its id for traceability assertions.
        $this->actingAs($user)->getJson("/api/v1/processes/{$process->id}/reviews/current")->assertOk();
        $reviewId = (int) DB::table('process_reviews')
            ->where('process_id', $process->id)
            ->orderByDesc('id')
            ->value('id');

        $response = $this->actingAs($user)->postJson("/api/v1/processes/{$process->id}/reviews/current/linked-data/actions", [
            'source_type' => 'duerp_danger',
            'source_id' => $dangerId,
            'title' => 'Action DUERP - Test',
            'description' => 'Action créée depuis la revue.',
            'deadline' => now()->addDays(10)->toDateString(),
            'type' => 'corrective',
            'priority' => 'high',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source_type', 'duerp_danger')
            ->assertJsonPath('data.source_id', $dangerId);

        $actionId = (int) $response->json('data.id');
        $action = DB::table('actions')->where('id', $actionId)->first();

        $this->assertNotNull($action);
        $this->assertSame('duerp_danger', (string) ($action->source_type ?? ''));
        $this->assertSame($dangerId, (int) ($action->source_id ?? 0));
        $this->assertSame($process->id, (int) ($action->process_id ?? 0));
        $this->assertSame($process->site_id, (int) ($action->site_id ?? 0));
        $this->assertSame($process->enterprise_id, (int) ($action->enterprise_id ?? 0));

        $traceability = json_decode((string) ($action->m7_d4_traceability ?? '[]'), true) ?: [];
        $this->assertSame('process_review_section_6', (string) ($traceability['origin'] ?? ''));
        $this->assertSame($reviewId, (int) ($traceability['process_review_id'] ?? 0));
        $this->assertSame('duerp_danger', (string) ($traceability['source_type'] ?? ''));
        $this->assertSame($dangerId, (int) ($traceability['source_id'] ?? 0));
    }

    public function test_can_create_linked_action_from_aes_row_with_traceability(): void
    {
        [$user, $process] = $this->makeUserAndProcess('admin_entreprise');
        $user->givePermissionTo(['process_reviews.read', 'process_reviews.update']);

        $aesId = DB::table('aspects_environnementaux')->insertGetId([
            'enterprise_id' => $process->enterprise_id,
            'site_id' => $process->site_id,
            'process_id' => $process->id,
            'type' => 'dechet',
            'designation' => 'Déchets spécifiques',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 4,
            'detectabilite' => 3,
            'aspect_significatif' => true,
            'objectifs_amelioration' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/v1/processes/{$process->id}/reviews/current")->assertOk();
        $reviewId = (int) DB::table('process_reviews')
            ->where('process_id', $process->id)
            ->orderByDesc('id')
            ->value('id');

        $response = $this->actingAs($user)->postJson("/api/v1/processes/{$process->id}/reviews/current/linked-data/actions", [
            'source_type' => 'aes_aspect',
            'source_id' => $aesId,
            'title' => 'Action AES - Test',
            'description' => 'Action AES créée depuis la revue.',
            'deadline' => now()->addDays(12)->toDateString(),
            'type' => 'preventive',
            'priority' => 'medium',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source_type', 'aes_aspect')
            ->assertJsonPath('data.source_id', $aesId);

        $actionId = (int) $response->json('data.id');
        $action = DB::table('actions')->where('id', $actionId)->first();

        $this->assertNotNull($action);
        $this->assertSame('aes_aspect', (string) ($action->source_type ?? ''));
        $this->assertSame($aesId, (int) ($action->source_id ?? 0));
        $this->assertSame($process->id, (int) ($action->process_id ?? 0));
        $this->assertSame($process->site_id, (int) ($action->site_id ?? 0));
        $this->assertSame($process->enterprise_id, (int) ($action->enterprise_id ?? 0));

        $traceability = json_decode((string) ($action->m7_d4_traceability ?? '[]'), true) ?: [];
        $this->assertSame('process_review_section_6', (string) ($traceability['origin'] ?? ''));
        $this->assertSame($reviewId, (int) ($traceability['process_review_id'] ?? 0));
        $this->assertSame('aes_aspect', (string) ($traceability['source_type'] ?? ''));
        $this->assertSame($aesId, (int) ($traceability['source_id'] ?? 0));
    }

    public function test_can_create_linked_action_from_incident_row_with_traceability(): void
    {
        [$user, $process] = $this->makeUserAndProcess('admin_entreprise');
        $user->givePermissionTo(['process_reviews.read', 'process_reviews.update']);

        $incidentId = DB::table('reclamations')->insertGetId([
            'ref' => 'REC-TEST-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'site_id' => $process->site_id,
            'user_id' => $user->id,
            'title' => 'Presque accident atelier',
            'description' => 'Glissade sans blessure sur zone humide.',
            'category' => 'other',
            'severity' => 'major',
            'status' => 'in_progress',
            'received_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/v1/processes/{$process->id}/reviews/current")->assertOk();
        $reviewId = (int) DB::table('process_reviews')
            ->where('process_id', $process->id)
            ->orderByDesc('id')
            ->value('id');

        $response = $this->actingAs($user)->postJson("/api/v1/processes/{$process->id}/reviews/current/linked-data/actions", [
            'source_type' => 'reclamation',
            'source_id' => $incidentId,
            'title' => 'Action Incident - Test',
            'description' => 'Action issue d’un signalement incident.',
            'deadline' => now()->addDays(7)->toDateString(),
            'type' => 'corrective',
            'priority' => 'high',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source_type', 'reclamation')
            ->assertJsonPath('data.source_id', $incidentId);

        $actionId = (int) $response->json('data.id');
        $action = DB::table('actions')->where('id', $actionId)->first();

        $this->assertNotNull($action);
        $this->assertSame('reclamation', (string) ($action->source_type ?? ''));
        $this->assertSame($incidentId, (int) ($action->source_id ?? 0));
        $this->assertSame($process->id, (int) ($action->process_id ?? 0));
        $this->assertSame($process->site_id, (int) ($action->site_id ?? 0));
        $this->assertSame($process->enterprise_id, (int) ($action->enterprise_id ?? 0));

        $traceability = json_decode((string) ($action->m7_d4_traceability ?? '[]'), true) ?: [];
        $this->assertSame('process_review_section_6', (string) ($traceability['origin'] ?? ''));
        $this->assertSame($reviewId, (int) ($traceability['process_review_id'] ?? 0));
        $this->assertSame('reclamation', (string) ($traceability['source_type'] ?? ''));
        $this->assertSame($incidentId, (int) ($traceability['source_id'] ?? 0));
    }

    private function makeUserAndProcess(string $role, ?int $siteId = null, ?int $enterpriseId = null): array
    {
        $siteAttributes = [];
        if ($siteId !== null) {
            $siteAttributes['id'] = $siteId;
        }
        if ($enterpriseId !== null) {
            $siteAttributes['enterprise_id'] = $enterpriseId;
        }

        $site = Site::factory()->create($siteAttributes);

        /** @var User $user */
        $user = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'role' => $role,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'pilot_id' => $user->id,
            'copilot_id' => null,
            'category' => 'pilotage',
        ]);

        return [$user, $process];
    }
}
