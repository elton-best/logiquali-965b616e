<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentCodeImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            'mfa.stepup',
            'check.subscription',
        ]);
    }

    public function test_approved_document_code_cannot_be_modified(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
            'code' => 'DOC-IMM-001',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/documents/{$document->id}", [
                'code' => 'DOC-IMM-999',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'code' => 'DOC-IMM-001',
        ]);
    }

    public function test_approved_document_cannot_be_deleted(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
            'code' => 'DOC-IMM-010',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_draft_document_can_be_deleted(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'workflow_status' => 'draft',
            'status' => 'draft',
            'code' => 'DOC-IMM-020',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_approved_document_modification_stays_on_current_version_until_final_approval(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
            'code' => 'DOC-IMM-030',
            'version' => '1.0',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->post("/api/v1/documents/{$document->id}/versions", [
                'file' => UploadedFile::fake()->create('version.pdf', 50, 'application/pdf'),
                'version' => '2.0',
                'change_summary' => 'Tentative de modification post-validation',
            ]);

        $response->assertCreated();
        $this->assertDatabaseCount((new DocumentVersion())->getTable(), 1);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'code' => 'DOC-IMM-030',
            'version' => '1.0',
            'status' => 'draft',
            'workflow_status' => 'draft',
        ]);

        $document->refresh();
        $this->assertSame('1.0', data_get($document->metadata, 'versioning.base_approved_version'));

        $ownerListResponse = $this->actingAs($user, 'sanctum')->getJson('/api/v1/documents');
        $ownerListResponse->assertOk();
        $this->assertTrue(collect($ownerListResponse->json())->pluck('id')->contains($document->id));

        $sameSiteUser = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
        $listResponse = $this->actingAs($sameSiteUser, 'sanctum')->getJson('/api/v1/documents');
        $listResponse->assertOk();
        $this->assertFalse(collect($listResponse->json())->pluck('id')->contains($document->id));

        $approver = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
        $approver->givePermissionTo('approve_documents');
        $document->update(['workflow_status' => 'pending_approval']);

        $approvalResponse = $this->actingAs($approver, 'sanctum')
            ->postJson("/api/v1/documents/{$document->id}/approve");

        $approvalResponse->assertOk();
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'code' => 'DOC-IMM-030',
            'version' => '2.0',
            'status' => 'approved',
            'workflow_status' => 'approved',
        ]);
        $this->assertDatabaseHas((new DocumentVersion())->getTable(), [
            'document_id' => $document->id,
            'version_number' => '2.0',
            'is_current' => true,
        ]);
    }
}
