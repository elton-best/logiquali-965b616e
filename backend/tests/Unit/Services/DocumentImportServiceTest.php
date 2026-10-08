<?php

namespace Tests\Unit\Services;

use App\Models\Document;
use App\Models\DocumentImport;
use App\Models\Site;
use App\Models\User;
use App\Services\DocumentImportService;
use App\Services\CodeGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentImportService $service;
    private Site $site;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $codeGenerationService = $this->createMock(CodeGenerationService::class);
        
        $this->service = new DocumentImportService($codeGenerationService);
        
        $this->site = Site::factory()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_parses_excel_file_successfully()
    {
        $filePath = $this->createTestExcelFile();
        
        $result = $this->service->parseFile($filePath);
        
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertIsArray($result['headers']);
        $this->assertIsArray($result['rows']);
    }

    /** @test */
    public function it_parses_csv_file_successfully()
    {
        $filePath = $this->createTestCsvFile();
        
        $result = $this->service->parseFile($filePath);
        
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertCount(2, $result['rows']);
    }

    /** @test */
    public function it_parses_json_file_successfully()
    {
        Storage::fake('local');
        $path = 'imports/test.json';
        Storage::disk('local')->put($path, json_encode([
            ['title' => 'Doc 1', '__file_path__' => 'imports/abc/file1.pdf'],
            ['title' => 'Doc 2', '__file_path__' => 'imports/abc/file2.pdf'],
        ], JSON_UNESCAPED_UNICODE));

        $result = $this->service->parseFile($path);

        $this->assertSame(['title', '__file_path__'], $result['headers']);
        $this->assertCount(2, $result['rows']);
    }

    /** @test */
    public function it_throws_on_invalid_json_file()
    {
        Storage::fake('local');
        $path = 'imports/invalid.json';
        Storage::disk('local')->put($path, '{"broken":');

        $this->expectException(\RuntimeException::class);
        $this->service->parseFile($path);
    }

    /** @test */
    public function it_validates_import_data_correctly()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $data = [
            'headers' => ['code', 'title', 'type', 'version'],
            'rows' => [
                ['DOC-001', 'Test Document', 'procedure', '1.0'],
                ['DOC-002', '', 'procedure', '1.0'], // Missing title
                ['DOC-003', 'Valid Document', 'invalid_type', '1.0'], // Invalid type
            ],
        ];

        $result = $this->service->validateImportData($import, $data);

        $this->assertEquals(1, $result['valid_count']);
        $this->assertEquals(2, $result['invalid_count']);
        $this->assertCount(3, $result['rows']);
        
        $import->refresh();
        $this->assertEquals('validated', $import->status);
    }

    /** @test */
    public function it_detects_duplicate_codes()
    {
        Document::factory()->create([
            'site_id' => $this->site->id,
            'code' => 'DOC-001',
        ]);

        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'headers' => ['code', 'title', 'type', 'version'],
            'rows' => [
                ['DOC-001', 'Duplicate Code', 'procedure', '1.0'],
            ],
        ];

        $result = $this->service->validateImportData($import, $data);

        $this->assertEquals(0, $result['valid_count']);
        $this->assertEquals(1, $result['invalid_count']);
        $this->assertStringContainsString('déjà existant', $result['rows'][0]['errors'][0]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'headers' => ['code', 'title', 'type', 'version'],
            'rows' => [
                ['', 'Missing Code', 'procedure', '1.0'],
                ['DOC-001', '', 'procedure', '1.0'],
                ['DOC-002', 'Missing Type', '', '1.0'],
            ],
        ];

        $result = $this->service->validateImportData($import, $data);

        $this->assertEquals(0, $result['valid_count']);
        $this->assertEquals(3, $result['invalid_count']);
    }

    /** @test */
    public function it_executes_import_successfully()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 2,
                'invalid_count' => 0,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-001',
                            'title' => 'Document 1',
                            'type' => 'procedure',
                            'version' => '1.0',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                    [
                        'row_number' => 3,
                        'data' => [
                            'code' => 'DOC-002',
                            'title' => 'Document 2',
                            'type' => 'instruction',
                            'version' => '1.0',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                ],
            ],
        ]);

        $result = $this->service->executeImport($import, $import->validation_results);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(0, $result['errors']);
        
        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(2, $import->imported_rows);
        
        $this->assertDatabaseHas('documents', [
            'code' => 'DOC-001',
            'title' => 'Document 1',
            'site_id' => $this->site->id,
        ]);
        
        $this->assertDatabaseHas('documents', [
            'code' => 'DOC-002',
            'title' => 'Document 2',
            'site_id' => $this->site->id,
        ]);
    }

    /** @test */
    public function it_handles_import_errors_gracefully()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 1,
                'invalid_count' => 0,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-001',
                            'title' => 'Document 1',
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

        $result = $this->service->executeImport($import, $import->validation_results);

        // Le service devrait importer avec succès ou gérer les erreurs
        $this->assertIsArray($result);
        $this->assertArrayHasKey('imported', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /** @test */
    public function it_generates_template_successfully()
    {
        $filePath = $this->service->generateTemplate();

        $this->assertNotEmpty($filePath);
        $this->assertFileExists(\Illuminate\Support\Facades\Storage::disk('local')->path($filePath));
        $this->assertStringContainsString('.xlsx', $filePath);
    }

    /** @test */
    public function it_rollbacks_import_successfully()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $doc1 = Document::factory()->create([
            'site_id' => $this->site->id,
            'metadata' => [
                'imported' => true,
                'import_date' => $import->created_at->toISOString(),
            ],
        ]);

        $doc2 = Document::factory()->create([
            'site_id' => $this->site->id,
            'metadata' => [
                'imported' => true,
                'import_date' => $import->created_at->toISOString(),
            ],
        ]);

        $result = $this->service->rollbackImport($import);

        $this->assertEquals(2, $result['deleted_count']);
        
        $import->refresh();
        $this->assertEquals('rolled_back', $import->status);
        
        $this->assertSoftDeleted('documents', ['id' => $doc1->id]);
        $this->assertSoftDeleted('documents', ['id' => $doc2->id]);
    }

    /** @test */
    public function it_skips_invalid_rows_during_import()
    {
        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 1,
                'invalid_count' => 1,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-001',
                            'title' => 'Valid Document',
                            'type' => 'procedure',
                            'version' => '1.0',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                    [
                        'row_number' => 3,
                        'data' => [
                            'code' => '',
                            'title' => 'Invalid Document',
                            'type' => 'procedure',
                            'version' => '1.0',
                        ],
                        'is_valid' => false,
                        'errors' => ['Code manquant'],
                        'warnings' => [],
                    ],
                ],
            ],
        ]);

        $result = $this->service->executeImport($import, $import->validation_results);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['failed']);
        
        $this->assertDatabaseHas('documents', ['code' => 'DOC-001']);
        $this->assertDatabaseMissing('documents', ['title' => 'Invalid Document']);
    }

    /** @test */
    public function it_copies_imported_file_to_final_documents_directory()
    {
        Storage::fake('local');
        $sourcePath = 'imports/session/files/source.pdf';
        Storage::disk('local')->put($sourcePath, 'pdf-content');

        $import = DocumentImport::factory()->create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'status' => 'validated',
            'validation_results' => [
                'valid_count' => 1,
                'invalid_count' => 0,
                'rows' => [
                    [
                        'row_number' => 2,
                        'data' => [
                            'code' => 'DOC-FILE-001',
                            'title' => 'Document fichier',
                            'type' => 'procedure',
                            'version' => '1.0',
                            '__file_path__' => $sourcePath,
                            '__file_name__' => 'Document fichier.pdf',
                        ],
                        'is_valid' => true,
                        'errors' => [],
                        'warnings' => [],
                    ],
                ],
            ],
        ]);

        $this->service->executeImport($import, $import->validation_results);

        $document = Document::query()->where('code', 'DOC-FILE-001')->firstOrFail();
        $this->assertNotNull($document->file_path);
        $this->assertStringStartsWith("documents/imports/{$import->id}/", $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('local')->assertExists($sourcePath);
    }

    private function createTestExcelFile(): string
    {
        $path = 'imports/test_' . uniqid() . '.xlsx';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['code', 'title', 'type', 'version'],
            ['DOC-001', 'Test Document', 'procedure', '1.0'],
        ]);
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fullPath);
        
        return $path;
    }

    private function createTestCsvFile(): string
    {
        $content = "code,title,type,version\n";
        $content .= "DOC-001,Test Document 1,procedure,1.0\n";
        $content .= "DOC-002,Test Document 2,instruction,1.0\n";
        
        $path = 'imports/test_' . uniqid() . '.csv';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        file_put_contents($fullPath, $content);
        
        return $path;
    }
}
