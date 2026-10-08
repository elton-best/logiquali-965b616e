<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\CodeStructurePart;
use App\Models\Document;
use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSourceTraceabilityTest extends TestCase
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

    public function test_store_persists_source_context_and_exposes_it_in_resource(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'pilot_id' => $user->id,
        ]);
        $config = DocumentTypeConfiguration::query()
            ->where('enterprise_id', $enterprise->id)
            ->where('abbreviation', 'PRC')
            ->first();

        if (!$config) {
            $config = DocumentTypeConfiguration::factory()->create([
                'enterprise_id' => $enterprise->id,
                'site_id' => $site->id,
                'scope' => 'site',
                'abbreviation' => 'PRC',
                'is_active' => true,
            ]);
        }

        if (!$config->structureParts()->exists()) {
            CodeStructurePart::create([
                'document_type_configuration_id' => $config->id,
                'part_order' => 1,
                'part_name' => 'Type',
                'part_type' => 'fixed_abbreviation',
                'part_length' => 3,
                'separator_after' => '-',
                'is_required' => true,
                'sequence_scope' => null,
            ]);
            CodeStructurePart::create([
                'document_type_configuration_id' => $config->id,
                'part_order' => 2,
                'part_name' => 'Sequence',
                'part_type' => 'sequence',
                'part_length' => 3,
                'separator_after' => null,
                'is_required' => true,
                'sequence_scope' => 'global',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/documents', [
                'site_id' => $site->id,
                'process_id' => $process->id,
                'title' => 'Procedure de traçabilité',
                'type' => 'procedure',
                'module_type' => 'information_documentee',
                'module_id' => 42,
                'source_type' => 'information_documentee',
                'source_module' => 'Information documentee',
                'source_submodule' => 'Inventaire documentaire',
                'source_section' => 'Import manuel',
            ]);

        $response->assertStatus(201);
        $documentId = (int) (
            data_get($response->json(), 'data.id')
            ?? data_get($response->json(), 'data.attributes.id')
            ?? 0
        );
        if ($documentId <= 0) {
            $documentId = (int) Document::query()->latest('id')->value('id');
        }
        $this->assertGreaterThan(0, $documentId);

        $document = Document::query()->findOrFail($documentId);
        $this->assertSame('information_documentee', $document->module_type);
        $this->assertSame(42, (int) $document->module_id);
        $this->assertSame('information_documentee', $document->source_type);
        $this->assertSame('Information documentee', data_get($document->metadata, 'source_context.module'));
        $this->assertSame('Inventaire documentaire', data_get($document->metadata, 'source_context.submodule'));
        $this->assertSame('Import manuel', data_get($document->metadata, 'source_context.section'));

        $payload = $response->json();
        $extract = static function (array $json, array $paths): mixed {
            foreach ($paths as $path) {
                $value = data_get($json, $path);
                if ($value !== null) {
                    return $value;
                }
            }

            return null;
        };

        $this->assertSame('Information documentee', $extract($payload, [
            'data.attributes.source_module',
            'data.source_module',
            'source_module',
            'data.attributes.metadata.source_context.module',
            'data.metadata.source_context.module',
            'metadata.source_context.module',
        ]));
        $this->assertSame('Inventaire documentaire', $extract($payload, [
            'data.attributes.source_submodule',
            'data.source_submodule',
            'source_submodule',
            'data.attributes.metadata.source_context.submodule',
            'data.metadata.source_context.submodule',
            'metadata.source_context.submodule',
        ]));
        $this->assertSame('Import manuel', $extract($payload, [
            'data.attributes.source_section',
            'data.source_section',
            'source_section',
            'data.attributes.metadata.source_context.section',
            'data.metadata.source_context.section',
            'metadata.source_context.section',
        ]));
    }

    public function test_update_merges_source_context_without_losing_existing_metadata(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'pilot_id' => $user->id,
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'author_id' => $user->id,
            'status' => 'draft',
            'metadata' => [
                'custom' => 'keep-me',
                'source_context' => [
                    'module' => 'Information documentee',
                    'submodule' => 'Inventaire documentaire',
                ],
            ],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/documents/{$document->id}", [
                'source_section' => 'Edition manuelle',
            ]);

        $response->assertOk();

        $document->refresh();
        $this->assertSame('keep-me', data_get($document->metadata, 'custom'));
        $this->assertSame('Information documentee', data_get($document->metadata, 'source_context.module'));
        $this->assertSame('Inventaire documentaire', data_get($document->metadata, 'source_context.submodule'));
        $this->assertSame('Edition manuelle', data_get($document->metadata, 'source_context.section'));
        $response->assertJsonPath('data.attributes.source_section', 'Edition manuelle');
    }
}
