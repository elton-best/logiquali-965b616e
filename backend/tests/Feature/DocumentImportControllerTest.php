<?php

namespace Tests\Feature;

use App\Models\DocumentImport;
use App\Models\DocumentTypeCatalog;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Site $site;
    private DocumentTypeCatalog $documentTypeCatalog;
    private Process $process;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            'mfa.stepup',
            'check.subscription',
        ]);
        
        Storage::fake('local');
        
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('import_documents');
        $this->site = Site::factory()->create();
        $this->documentTypeCatalog = DocumentTypeCatalog::query()->create([
            'enterprise_id' => $this->site->enterprise_id,
            'site_id' => $this->site->id,
            'name' => 'Procédure',
            'abbreviation' => 'PRC',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $this->process = Process::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $this->documentTypeCatalog->processes()->sync([$this->process->id]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_uploads_file_successfully()
    {
        $file = $this->createTestExcelFile();

        $response = $this->postJson('/api/v1/document-imports/upload', [
            'file' => $file,
            'site_id' => $this->site->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'import_id',
                    'filename',
                    'total_rows',
                    'headers',
                ],
            ]);

        $this->assertDatabaseHas('document_imports', [
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_file_format()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->postJson('/api/v1/document-imports/upload', [
            'file' => $file,
            'site_id' => $this->site->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_rejects_file_too_large()
    {
        $file = UploadedFile::fake()->create('test.xlsx', 11000); // 11 MB

        $response = $this->postJson('/api/v1/document-imports/upload', [
            'file' => $file,
            'site_id' => $this->site->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_uploads_multiple_files_and_creates_manifest_import()
    {
        $file1 = UploadedFile::fake()->create('Procedure A.pdf', 50, 'application/pdf');
        $file2 = UploadedFile::fake()->create('Instruction B.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->postJson('/api/v1/document-imports/upload-files', [
            'files' => [$file1, $file2],
            'site_id' => $this->site->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.headers.0', 'title')
            ->assertJsonPath('data.headers.1', '__file_path__')
            ->assertJsonPath('data.headers.2', '__file_name__');

        $importId = (int) $response->json('data.import_id');
        $import = DocumentImport::findOrFail($importId);
        $this->assertStringContainsString('imports/', $import->file_path);
        Storage::disk('local')->assertExists($import->file_path);
    }

    /** @test */
    public function it_validates_import_successfully()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'file_path' => $this->createTestExcelFileInStorage(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/validate", [
            'document_type_catalog_id' => $this->documentTypeCatalog->id,
            'process_id' => $this->process->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'import_id',
                    'status',
                    'validation_results' => [
                        'valid_count',
                        'invalid_count',
                        'rows',
                    ],
                ],
            ]);

        $import->refresh();
        $this->assertEquals('validated', $import->status);
    }

    /** @test */
    public function it_prevents_validation_by_unauthorized_user()
    {
        $otherUser = User::factory()->create();
        $import = DocumentImport::factory()->create([
            'user_id' => $otherUser->id,
            'site_id' => $this->site->id,
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/validate", [
            'document_type_catalog_id' => $this->documentTypeCatalog->id,
            'process_id' => $this->process->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_type_and_process_for_validation()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'file_path' => $this->createTestExcelFileInStorage(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/validate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_catalog_id', 'process_id']);
    }

    /** @test */
    public function it_executes_import_successfully()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 1,
                'invalid_count' => 0,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-001',
                            'title' => 'Test Document',
                            'type' => 'procedure',
                            'version' => '1.0',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/execute", [
            'document_type_catalog_id' => $this->documentTypeCatalog->id,
            'process_id' => $this->process->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'import_id',
                    'status',
                    'stats' => [
                        'imported',
                        'failed',
                        'errors',
                    ],
                ],
            ]);

        $import->refresh();
        $this->assertEquals('completed', $import->status);
    }

    /** @test */
    public function it_prevents_execution_of_non_validated_import()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/execute");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_shows_import_details()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        $response = $this->getJson("/api/v1/document-imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'status',
                    'total_rows',
                    'valid_rows',
                    'invalid_rows',
                    'imported_rows',
                    'failed_rows',
                ],
            ]);
    }

    /** @test */
    public function it_lists_user_imports()
    {
        DocumentImport::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        // Create import for another user (should not be listed)
        $otherUser = User::factory()->create();
        DocumentImport::factory()->create([
            'user_id' => $otherUser->id,
            'site_id' => $this->site->id,
        ]);

        $response = $this->getJson('/api/v1/document-imports');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data.data');
    }

    /** @test */
    public function it_filters_imports_by_status()
    {
        DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
        ]);

        $response = $this->getJson('/api/v1/document-imports?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    /** @test */
    public function it_downloads_template()
    {
        $response = $this->getJson('/api/v1/document-imports/template');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'download_url',
                    'filename',
                ],
            ]);
    }

    /** @test */
    public function it_rollbacks_import()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/rollback");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'deleted_count',
                    'message',
                ],
            ]);

        $import->refresh();
        $this->assertEquals('rolled_back', $import->status);
    }

    /** @test */
    public function it_prevents_rollback_of_non_completed_import()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/rollback");

        $response->assertStatus(400);
    }

    /** @test */
    public function it_rejects_execute_when_process_not_allowed_for_type()
    {
        $otherProcess = Process::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 1,
                'invalid_count' => 0,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-001',
                            'title' => 'Test Document',
                            'type' => 'procedure',
                            'version' => '1.0',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->postJson("/api/v1/document-imports/{$import->id}/execute", [
            'document_type_catalog_id' => $this->documentTypeCatalog->id,
            'process_id' => $otherProcess->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Le processus sélectionné n’est pas autorisé pour ce type documentaire.');
    }

    /** @test */
    public function it_deletes_import()
    {
        $import = DocumentImport::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'file_path' => 'imports/test.xlsx',
        ]);

        Storage::put($import->file_path, 'test content');

        $response = $this->deleteJson("/api/v1/document-imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('document_imports', ['id' => $import->id]);
        Storage::assertMissing($import->file_path);
    }

    /** @test */
    public function it_prevents_deletion_by_unauthorized_user()
    {
        $otherUser = User::factory()->create();
        $import = DocumentImport::factory()->create([
            'user_id' => $otherUser->id,
            'site_id' => $this->site->id,
        ]);

        $response = $this->deleteJson("/api/v1/document-imports/{$import->id}");

        $response->assertStatus(403);
    }

    private function createTestExcelFile(): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['code', 'title', 'type', 'version'],
            ['DOC-001', 'Test Document', 'procedure', '1.0'],
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFile);

        return new UploadedFile($tempFile, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function createTestExcelFileInStorage(): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['code', 'title', 'type', 'version'],
            ['DOC-001', 'Test Document', 'procedure', '1.0'],
        ]);

        $path = 'imports/test_' . time() . '.xlsx';
        Storage::makeDirectory('imports');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save(Storage::disk('local')->path($path));

        return $path;
    }
}
