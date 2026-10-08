<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\ProcessCatalog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessCatalogTest extends TestCase
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
    }

    public function test_company_user_can_create_site_scoped_process(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        /** @var User $user */
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/process-catalogs', [
                'site_id' => $site->id,
                'name' => 'Qualité',
                'abbreviation' => 'qam',
                'internal_code' => 'p-01',
                'display_order' => 1,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('abbreviation', 'QAM');
        $response->assertJsonPath('internal_code', 'P-01');

        $this->assertDatabaseHas('process_catalogs', [
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Qualité',
            'abbreviation' => 'QAM',
            'internal_code' => 'P-01',
        ]);
    }

    public function test_company_user_cannot_create_process_for_other_enterprise_site(): void
    {
        $enterpriseA = Enterprise::factory()->create();
        $siteA = Site::factory()->create(['enterprise_id' => $enterpriseA->id]);
        /** @var User $user */
        $user = User::factory()->create([
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $enterpriseB = Enterprise::factory()->create();
        $siteB = Site::factory()->create(['enterprise_id' => $enterpriseB->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/process-catalogs', [
                'site_id' => $siteB->id,
                'name' => 'Support',
                'abbreviation' => 'SUP',
            ]);

        $response->assertStatus(403);
    }

    public function test_abbreviation_must_be_unique_per_enterprise_site_scope(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        /** @var User $user */
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        ProcessCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Qualité',
            'abbreviation' => 'QAM',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/process-catalogs', [
                'site_id' => $site->id,
                'name' => 'Qualité bis',
                'abbreviation' => 'qam',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['abbreviation']);
    }

    public function test_index_is_scoped_to_actor_enterprise(): void
    {
        $enterpriseA = Enterprise::factory()->create();
        $siteA = Site::factory()->create(['enterprise_id' => $enterpriseA->id]);
        /** @var User $userA */
        $userA = User::factory()->create([
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $enterpriseB = Enterprise::factory()->create();
        $siteB = Site::factory()->create(['enterprise_id' => $enterpriseB->id]);

        ProcessCatalog::query()->create([
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
            'name' => 'Process A',
            'abbreviation' => 'PRA',
            'is_active' => true,
        ]);

        ProcessCatalog::query()->create([
            'enterprise_id' => $enterpriseB->id,
            'site_id' => $siteB->id,
            'name' => 'Process B',
            'abbreviation' => 'PRB',
            'is_active' => true,
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/process-catalogs');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Process A']);
        $response->assertJsonMissing(['name' => 'Process B']);
    }
}
