<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\DocumentTypeCatalog;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeCatalogTest extends TestCase
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

    public function test_company_user_can_create_site_scoped_document_type(): void
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
            ->postJson('/api/v1/document-type-catalogs', [
                'site_id' => $site->id,
                'name' => 'Politique',
                'abbreviation' => 'pol',
                'description' => 'Type de politique',
                'display_order' => 1,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('abbreviation', 'POL');
        $this->assertDatabaseHas('document_type_catalogs', [
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Politique',
            'abbreviation' => 'POL',
        ]);
    }

    public function test_company_user_cannot_create_type_for_other_enterprise_site(): void
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
            ->postJson('/api/v1/document-type-catalogs', [
                'site_id' => $siteB->id,
                'name' => 'Document externe',
                'abbreviation' => 'EXT',
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

        DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Politique',
            'abbreviation' => 'POL',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/document-type-catalogs', [
                'site_id' => $site->id,
                'name' => 'Politique bis',
                'abbreviation' => 'pol',
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

        DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterpriseA->id,
            'site_id' => $siteA->id,
            'name' => 'Type A',
            'abbreviation' => 'TAA',
            'is_active' => true,
        ]);

        DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterpriseB->id,
            'site_id' => $siteB->id,
            'name' => 'Type B',
            'abbreviation' => 'TBB',
            'is_active' => true,
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/document-type-catalogs');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Type A']);
        $response->assertJsonMissing(['name' => 'Type B']);
    }
}

