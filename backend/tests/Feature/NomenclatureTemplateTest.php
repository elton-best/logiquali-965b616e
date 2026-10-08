<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\DocumentTypeCatalog;
use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use App\Models\NomenclatureTemplate;
use App\Models\Process;
use App\Models\ProcessCatalog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomenclatureTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);
    }

    public function test_validate_template_requires_sequence_token(): void
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
            ->postJson('/api/v1/nomenclature-templates/validate-template', [
                'format_structure' => [
                    ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'PROCESS_ABBR'],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format_structure']);
    }

    public function test_validate_template_accepts_valid_payload(): void
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
            ->postJson('/api/v1/nomenclature-templates/validate-template', [
                'format_structure' => [
                    ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'PROCESS_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'SEQUENCE', 'length' => 3],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.valid', true);
    }

    public function test_simulate_samples_generates_codes(): void
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
            ->postJson('/api/v1/nomenclature-templates/simulate-samples', [
                'sample_count' => 2,
                'context' => [
                    'doc_type_abbr' => 'POL',
                    'process_abbr' => 'QAM',
                ],
                'format_structure' => [
                    ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'PROCESS_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'SEQUENCE', 'length' => 3],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.code', 'POL_QAM_001');
        $response->assertJsonPath('data.1.code', 'POL_QAM_002');
    }

    public function test_publish_archives_previous_published_template_same_scope(): void
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

        $docType = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Politique',
            'abbreviation' => 'POL',
            'is_active' => true,
        ]);

        $process = ProcessCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Qualité',
            'abbreviation' => 'QAM',
            'is_active' => true,
        ]);

        NomenclatureTemplate::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'document_type_catalog_id' => $docType->id,
            'process_catalog_id' => $process->id,
            'name' => 'v1',
            'version' => 1,
            'status' => 'published',
            'is_active' => true,
            'format_structure' => [
                ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                ['type' => 'separator', 'value' => '_'],
                ['type' => 'token', 'token' => 'SEQUENCE'],
            ],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/nomenclature-templates', [
                'site_id' => $site->id,
                'document_type_catalog_id' => $docType->id,
                'process_catalog_id' => $process->id,
                'name' => 'v2',
                'version' => 2,
                'status' => 'published',
                'format_structure' => [
                    ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'PROCESS_ABBR'],
                    ['type' => 'separator', 'value' => '_'],
                    ['type' => 'token', 'token' => 'SEQUENCE'],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('nomenclature_templates', [
            'name' => 'v1',
            'status' => 'archived',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('nomenclature_templates', [
            'name' => 'v2',
            'status' => 'published',
        ]);
    }

    public function test_update_to_published_archives_other_active_templates(): void
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

        $docType = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Politique',
            'abbreviation' => 'POL',
            'is_active' => true,
        ]);

        $process = ProcessCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Qualité',
            'abbreviation' => 'QAM',
            'is_active' => true,
        ]);

        $published = NomenclatureTemplate::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'document_type_catalog_id' => $docType->id,
            'process_catalog_id' => $process->id,
            'name' => 'v1',
            'version' => 1,
            'status' => 'published',
            'is_active' => true,
            'format_structure' => [
                ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                ['type' => 'separator', 'value' => '_'],
                ['type' => 'token', 'token' => 'SEQUENCE'],
            ],
        ]);

        $draft = NomenclatureTemplate::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'document_type_catalog_id' => $docType->id,
            'process_catalog_id' => $process->id,
            'name' => 'v2',
            'version' => 2,
            'status' => 'draft',
            'is_active' => false,
            'format_structure' => [
                ['type' => 'token', 'token' => 'DOC_TYPE_ABBR'],
                ['type' => 'separator', 'value' => '_'],
                ['type' => 'token', 'token' => 'PROCESS_ABBR'],
                ['type' => 'separator', 'value' => '_'],
                ['type' => 'token', 'token' => 'SEQUENCE'],
            ],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/nomenclature-templates/{$draft->id}", [
                'status' => 'published',
                'separator' => '_',
                'format_structure' => [
                    ['order' => 1, 'type' => 'document_type', 'label' => 'Type', 'length' => 3],
                    ['order' => 2, 'type' => 'process_code', 'label' => 'Processus', 'length' => 3],
                    ['order' => 3, 'type' => 'sequential_number', 'label' => 'Sequence', 'length' => 3],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('nomenclature_templates', [
            'id' => $published->id,
            'status' => 'archived',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('nomenclature_templates', [
            'id' => $draft->id,
            'status' => 'published',
            'is_active' => true,
        ]);
    }

    public function test_publishing_template_synchronizes_technical_code_configuration(): void
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

        $docType = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Enregistrement',
            'abbreviation' => 'ENR',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/nomenclature-templates', [
                'site_id' => $site->id,
                'document_type_catalog_id' => $docType->id,
                'name' => 'Template ENR',
                'status' => 'published',
                'separator' => '_',
                'format_structure' => [
                    ['order' => 1, 'type' => 'document_type', 'label' => 'Type', 'length' => 3],
                    ['order' => 2, 'type' => 'process_code', 'label' => 'Processus', 'length' => 3],
                    ['order' => 3, 'type' => 'sequential_number', 'label' => 'Sequence', 'length' => 3],
                ],
            ]);

        $response->assertStatus(201);

        $config = DocumentTypeConfiguration::query()
            ->where('enterprise_id', $enterprise->id)
            ->where('abbreviation', 'ENR')
            ->firstOrFail();

        $this->assertSame('enterprise', $config->scope);
        $this->assertDatabaseHas('code_structure_parts', [
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_type' => 'fixed_abbreviation',
            'separator_after' => '_',
        ]);
        $this->assertDatabaseHas('code_structure_parts', [
            'document_type_configuration_id' => $config->id,
            'part_order' => 2,
            'part_type' => 'process_abbreviation',
            'separator_after' => '_',
        ]);
        $this->assertDatabaseHas('code_structure_parts', [
            'document_type_configuration_id' => $config->id,
            'part_order' => 3,
            'part_type' => 'sequence',
            'separator_after' => null,
        ]);
    }

    public function test_document_code_preview_uses_published_nomenclature_template(): void
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
        $process = Process::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'title' => 'Pilotage',
            'abbreviation' => 'PIL',
        ]);
        $docType = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'name' => 'Enregistrement',
            'abbreviation' => 'ENR',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/nomenclature-templates', [
                'site_id' => $site->id,
                'document_type_catalog_id' => $docType->id,
                'name' => 'Template ENR',
                'status' => 'published',
                'separator' => '_',
                'format_structure' => [
                    ['order' => 1, 'type' => 'document_type', 'label' => 'Type', 'length' => 3],
                    ['order' => 2, 'type' => 'process_code', 'label' => 'Processus', 'length' => 3],
                    ['order' => 3, 'type' => 'sequential_number', 'label' => 'Sequence', 'length' => 3],
                ],
            ])
            ->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/documents/preview-code?site_id={$site->id}&type=ENR&process_id={$process->id}");

        $response->assertOk();
        $response->assertJsonPath('data.code', 'ENR_PIL_001');
        $response->assertJsonPath('data.nomenclature_template_version', 1);
        $this->assertNotNull($response->json('data.nomenclature_template_id'));
    }
}
