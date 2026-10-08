<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

class DocumentTypeConfigurationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Enterprise $enterprise;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            \App\Http\Middleware\ForceCompanySetup::class,
            'mfa.stepup',
            'check.subscription',
            'force.company',
        ]);
        
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        
        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
        
        $this->user->givePermissionTo(['view_nomenclature', 'configure_nomenclature']);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_lists_configurations()
    {
        DocumentTypeConfiguration::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $response = $this->getJson('/api/v1/document-type-configurations');

        if ($response->status() !== 200) {
            dump($response->json());
            dump($response->status());
        }

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_creates_configuration()
    {
        $data = [
            'type_code' => 'TEST',
            'type_label' => 'Test Configuration',
            'scope' => 'site',
            'is_active' => true,
            'code_structure' => [
                ['part_type' => 'type_code', 'order' => 1],
                ['part_type' => 'year', 'order' => 2],
                ['part_type' => 'sequence', 'order' => 3, 'length' => 4, 'sequence_scope' => 'by_type_year'],
            ],
        ];

        $response = $this->postJson('/api/v1/document-type-configurations', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_type_configurations', [
            'abbreviation' => 'TEST',
            'enterprise_id' => $this->enterprise->id
        ]);
    }

    /** @test */
    public function it_creates_configuration_with_modern_schema()
    {
        $typeCode = 'PRC' . random_int(10, 99);
        $data = [
            'type_code' => $typeCode,
            'type_label' => 'Procedure',
            'scope' => 'site',
            'is_active' => true,
            'code_structure' => [
                ['part_type' => 'type_code', 'order' => 1],
                ['part_type' => 'year', 'order' => 2],
                ['part_type' => 'sequence', 'order' => 3, 'length' => 4, 'sequence_scope' => 'by_type_year'],
            ],
        ];

        $response = $this->postJson('/api/v1/document-type-configurations', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.type_code', $typeCode)
            ->assertJsonPath('data.type_label', 'Procedure');
    }

    /** @test */
    public function it_shows_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $response = $this->getJson("/api/v1/document-type-configurations/{$config->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $config->id);
    }

    /** @test */
    public function it_previews_code_with_modern_schema_payload()
    {
        $response = $this->postJson('/api/v1/document-type-configurations/preview-code', [
            'type_code' => 'PRC',
            'code_structure' => [
                ['part_type' => 'type_code', 'order' => 1],
                ['part_type' => 'year', 'order' => 2],
                ['part_type' => 'sequence', 'order' => 3, 'length' => 3, 'sequence_scope' => 'by_type_year'],
            ],
            'year' => 2026,
            'month' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.preview', fn ($value) => is_string($value) && str_contains($value, 'PRC'));
    }

    /** @test */
    public function it_updates_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'name' => 'Original'
        ]);

        $response = $this->putJson("/api/v1/document-type-configurations/{$config->id}", [
            'type_label' => 'Updated'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('document_type_configurations', [
            'id' => $config->id,
            'name' => 'Updated'
        ]);
    }

    /** @test */
    public function it_deletes_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $response = $this->deleteJson("/api/v1/document-type-configurations/{$config->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('document_type_configurations', ['id' => $config->id]);
    }

    /** @test */
    public function it_toggles_active_status()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);

        $response = $this->postJson("/api/v1/document-type-configurations/{$config->id}/toggle-active");

        $response->assertStatus(200);
        $this->assertDatabaseHas('document_type_configurations', [
            'id' => $config->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_duplicates_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'abbreviation' => 'ORIG',
            'name' => 'Original'
        ]);

        $response = $this->postJson("/api/v1/document-type-configurations/{$config->id}/duplicate", [
            'type_code' => 'DUP',
            'type_label' => 'Duplicate'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_type_configurations', [
            'abbreviation' => 'DUP',
            'name' => 'Duplicate'
        ]);
    }

    /** @test */
    public function it_gets_visibility_stats()
    {
        DocumentTypeConfiguration::factory()->count(5)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/document-type-configurations/visibility-stats');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['total', 'by_scope', 'by_status']]);
    }

    /** @test */
    public function it_applies_advanced_filters()
    {
        DocumentTypeConfiguration::factory()->count(10)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/document-type-configurations/advanced-filters?is_active=true');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs(User::factory()->create(), []);

        $response = $this->getJson('/api/v1/document-type-configurations');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $response = $this->postJson('/api/v1/document-type-configurations', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type_code', 'type_label', 'code_structure']);
    }

    /** @test */
    public function it_shares_configuration_with_sites()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $site1 = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        $response = $this->postJson("/api/v1/document-type-configurations/{$config->id}/share-with-sites", [
            'site_ids' => [$site1->id, $site2->id]
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['success', 'errors']]);
    }

    /** @test */
    public function it_skips_existing_site_configuration_when_conflict_strategy_is_skip()
    {
        $source = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'abbreviation' => 'SKP',
            'name' => 'Source config',
        ]);

        $response = $this->postJson("/api/v1/document-type-configurations/{$source->id}/share-with-sites", [
            'site_ids' => [$this->site->id],
            'conflict_strategy' => 'skip',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success.0.action', 'skipped')
            ->assertJsonPath('data.success.0.config_id', $source->id);
    }

    /** @test */
    public function it_overrides_existing_site_configuration_when_conflict_strategy_is_override()
    {
        $source = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'abbreviation' => 'OVR',
            'name' => 'New canonical name',
            'description' => 'Source description',
        ]);

        $response = $this->postJson("/api/v1/document-type-configurations/{$source->id}/share-with-sites", [
            'site_ids' => [$this->site->id],
            'conflict_strategy' => 'override',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success.0.action', 'overridden')
            ->assertJsonPath('data.success.0.config_id', $source->id);

        $this->assertDatabaseHas('document_type_configurations', [
            'id' => $source->id,
            'name' => 'New canonical name',
            'description' => 'Source description',
        ]);
    }

    /** @test */
    public function it_gets_available_sites_for_sharing()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        Site::factory()->count(3)->create(['enterprise_id' => $this->enterprise->id]);

        $response = $this->getJson("/api/v1/document-type-configurations/{$config->id}/available-sites-for-sharing");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_filters_by_scope()
    {
        DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => null,
            'scope' => 'enterprise'
        ]);

        DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'scope' => 'site'
        ]);

        $response = $this->getJson('/api/v1/document-type-configurations?scope=site');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_filters_by_active_status()
    {
        DocumentTypeConfiguration::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);

        DocumentTypeConfiguration::factory()->count(2)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => false
        ]);

        $response = $this->getJson('/api/v1/document-type-configurations?is_active=true');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_notifies_other_sites_when_site_configuration_is_updated()
    {
        $otherSite = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $recipient = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $otherSite->id,
            'role' => 'site_manager',
            'is_active' => true,
            'collaborator_approval_status' => 'approved',
        ]);

        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'scope' => 'site',
            'abbreviation' => 'NTF' . random_int(10, 99),
        ]);

        $this->putJson("/api/v1/document-type-configurations/{$config->id}", [
            'type_label' => 'Config modifiée',
        ])->assertStatus(200);

        $notification = DatabaseNotification::query()
            ->where('notifiable_id', $recipient->id)
            ->where('notifiable_type', User::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('nomenclature_propagation', data_get($notification->data, 'quick_action.type'));
        $this->assertSame($config->id, (int) data_get($notification->data, 'quick_action.configuration_id'));
        $this->assertSame($otherSite->id, (int) data_get($notification->data, 'quick_action.target_site_id'));
    }
}
