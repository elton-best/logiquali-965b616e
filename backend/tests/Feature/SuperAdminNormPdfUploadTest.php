<?php

namespace Tests\Feature;

use App\Models\Norm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminNormPdfUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_upload_pdf_for_norm(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'user_type' => 'super_admin',
            'is_active' => true,
        ]);

        $norm = Norm::factory()->create([
            'status' => 'published',
        ]);

        $response = $this
            ->withoutMiddleware()
            ->actingAs($user, 'sanctum')
            ->post('/api/v1/superadmin/norms/' . $norm->id . '/pdf', [
                'file' => UploadedFile::fake()->create('norme.pdf', 150, 'application/pdf'),
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.has_pdf_document', true);
        $response->assertJsonPath('data.pdf_original_name', 'norme.pdf');

        $norm->refresh();
        $this->assertNotNull($norm->pdf_file_path);
        Storage::disk('public')->assertExists($norm->pdf_file_path);
    }

    public function test_excel_import_is_blocked_when_norm_already_has_pdf(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'user_type' => 'super_admin',
            'is_active' => true,
        ]);

        $pdf = UploadedFile::fake()->create('norme.pdf', 150, 'application/pdf');
        $pdfPath = $pdf->store('norms/pdfs', 'public');

        $norm = Norm::factory()->create([
            'code' => 'ISO-9001',
            'name' => 'ISO 9001',
            'domain' => 'quality',
            'status' => 'published',
            'pdf_file_path' => $pdfPath,
            'pdf_original_name' => 'norme.pdf',
            'pdf_uploaded_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware()
            ->actingAs($user, 'sanctum')
            ->post('/api/v1/superadmin/norms/import', [
                'file' => UploadedFile::fake()->create('norme.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                'code' => $norm->code,
                'name' => $norm->name,
                'domain' => $norm->domain,
                'version_code' => '2015',
                'action' => 'replace',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error_code', 'SUPERADMIN_NORM_IMPORT_BLOCKED_BY_PDF');
    }
}
