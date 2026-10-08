<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentTypeCatalog;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DocumentInventoryExportWorkflowGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            'mfa.stepup',
            'check.subscription',
        ]);
    }

    public function test_export_inventory_includes_only_approved_documents(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
        ]);

        $process = Process::factory()->create([
            'site_id' => $site->id,
        ]);

        $catalog = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'name' => 'Procédure',
            'abbreviation' => 'PRC',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $catalog->processes()->sync([$process->id]);

        Document::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'title' => 'Document approved',
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        Document::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'title' => 'Document draft',
            'workflow_status' => 'draft',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/documents/export-inventory?site_id=' . $site->id
                . '&document_type_catalog_id=' . $catalog->id
                . '&process_id=' . $process->id);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        /** @var BinaryFileResponse $binary */
        $binary = $response->baseResponse;
        $filePath = $binary->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $flat = json_encode($rows, JSON_UNESCAPED_UNICODE);

        $this->assertIsString($flat);
        $this->assertStringContainsString('Document approved', $flat);
        $this->assertStringNotContainsString('Document draft', $flat);
    }

    public function test_export_inventory_requires_type_and_process(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/documents/export-inventory?site_id=' . $site->id);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['document_type_catalog_id', 'process_id']);
    }

    public function test_export_inventory_rejects_process_not_allowed_for_catalog(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
        ]);

        $catalog = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'name' => 'Procédure',
            'abbreviation' => 'PRC',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $allowedProcess = Process::factory()->create(['site_id' => $site->id]);
        $blockedProcess = Process::factory()->create(['site_id' => $site->id]);
        $catalog->processes()->sync([$allowedProcess->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/documents/export-inventory?site_id=' . $site->id
                . '&document_type_catalog_id=' . $catalog->id
                . '&process_id=' . $blockedProcess->id);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'message',
            'Le processus sélectionné n’est pas autorisé pour ce type documentaire.'
        );
    }
}
