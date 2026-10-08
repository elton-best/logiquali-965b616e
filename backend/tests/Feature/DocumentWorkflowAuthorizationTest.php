<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Document;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentWorkflowAuthorizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $author;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherSite;
    protected Document $document;

    public function setUp(): void
    {
        parent::setUp();

        $enterprise = Enterprise::factory()->approved()->create();
        $enterprise->forceFill([
            'status' => 'active',
            'approval_status' => 'approved',
            'field' => 'Services',
            'domaine_activite_set' => true,
        ])->save();

        // Setup sites
        $this->site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_active' => true,
        ]);
        $this->otherSite = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_active' => true,
        ]);

        $this->seedActiveSubscriptionCoverageForSite($this->site);
        $this->seedActiveSubscriptionCoverageForSite($this->otherSite);

        // Setup users
        $this->author = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterprise->id,
            'site_id' => $this->site->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->otherUser = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterprise->id,
            'site_id' => $this->otherSite->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Setup document
        $this->document = Document::factory()->create([
            'site_id' => $this->site->id,
            'author_id' => $this->author->id,
            'code' => 'DOC-001-001',
            'workflow_status' => 'draft',
        ]);
    }

    /**
     * Test : Seul l'auteur du document peut confirmer le code (IDOR prevention).
     */
    public function test_only_author_can_confirm_code()
    {
        $this->actingAs($this->otherUser);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/confirm-code",
            [
                'needs_verification' => true,
                'confirmed_code' => 'DOC-001-001',
            ]
        );

        $response->assertStatus(403);
    }

    /**
     * Test : Tenant isolation - user d'un autre site ne peut pas accéder au document.
     */
    public function test_cross_tenant_access_denied_on_confirm_code()
    {
        // L'utilisateur d'un autre site essaie d'accéder
        $this->actingAs($this->otherUser);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/confirm-code",
            [
                'needs_verification' => true,
                'confirmed_code' => 'DOC-001-001',
            ]
        );

        $response->assertStatus(403);
    }

    /**
     * Test : L'auteur peut confirmer son propre code.
     */
    public function test_author_can_confirm_code()
    {
        $this->actingAs($this->author);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/confirm-code",
            [
                'needs_verification' => true,
                'confirmed_code' => 'DOC-001-001',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('message', 'Code confirmé, document en cours de traitement.');
    }

    /**
     * Test : Verify requires verify_documents permission (without transition logic).
     */
    public function test_verify_requires_permission()
    {
        $this->document->update(['workflow_status' => 'pending_verification']);

        $this->actingAs($this->author);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/verify"
        );

        $response->assertStatus(403); // Permission denied
    }

    /**
     * Test : Approve requires approve_documents permission.
     */
    public function test_approve_requires_permission()
    {
        $this->document->update(['workflow_status' => 'pending_approval']);

        $this->actingAs($this->author);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/approve"
        );

        $response->assertStatus(403); // Permission denied
    }

    /**
     * Test : Code mismatch is rejected.
     */
    public function test_code_mismatch_rejected()
    {
        $this->actingAs($this->author);

        $response = $this->postJson(
            "/api/v1/documents/{$this->document->id}/confirm-code",
            [
                'needs_verification' => true,
                'confirmed_code' => 'WRONG-CODE',
            ]
        );

        $response->assertStatus(422);
    }

    public function test_generated_source_state_returns_latest_export()
    {
        $this->document->update([
            'metadata' => [
                'source' => 'generated_process_document',
                'source_type' => 'swot_pestel',
                'source_model_id' => (string) $this->site->id,
                'document_kind' => 'context',
                'source_updated_at' => now()->subMinute()->toISOString(),
            ],
            'workflow_status' => 'draft',
        ]);

        $this->actingAs($this->author);

        $response = $this->getJson('/api/v1/documents/generated-source-state?' . http_build_query([
            'source_type' => 'swot_pestel',
            'source_id' => $this->site->id,
            'document_kind' => 'context',
            'source_updated_at' => now()->subMinutes(2)->toISOString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.document.id', $this->document->id)
            ->assertJsonPath('data.has_export', true)
            ->assertJsonPath('data.can_submit_existing_export', true)
            ->assertJsonPath('data.source_dirty', false);
    }

    public function test_generated_source_state_marks_submitted_document_as_not_submittable()
    {
        $this->document->update([
            'metadata' => [
                'source' => 'generated_process_document',
                'source_type' => 'swot_pestel',
                'source_model_id' => (string) $this->site->id,
                'document_kind' => 'context',
                'source_updated_at' => now()->subMinute()->toISOString(),
            ],
            'workflow_status' => 'pending_verification',
        ]);

        $this->actingAs($this->author);

        $response = $this->getJson('/api/v1/documents/generated-source-state?' . http_build_query([
            'source_type' => 'swot_pestel',
            'source_id' => $this->site->id,
            'document_kind' => 'context',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.is_submitted', true)
            ->assertJsonPath('data.can_submit_existing_export', false);
    }

    public function test_generated_source_state_marks_source_dirty_after_export()
    {
        $exportedAt = now()->subDay();
        $this->document->update([
            'metadata' => [
                'source' => 'generated_process_document',
                'source_type' => 'swot_pestel',
                'source_model_id' => (string) $this->site->id,
                'document_kind' => 'context',
                'source_updated_at' => $exportedAt->toISOString(),
            ],
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        $this->actingAs($this->author);

        $response = $this->getJson('/api/v1/documents/generated-source-state?' . http_build_query([
            'source_type' => 'swot_pestel',
            'source_id' => $this->site->id,
            'document_kind' => 'context',
            'source_updated_at' => now()->toISOString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.source_dirty', true)
            ->assertJsonPath('data.requires_new_export', true)
            ->assertJsonPath('data.can_submit_existing_export', false);
    }

    private function seedActiveSubscriptionCoverageForSite(Site $site): void
    {
        $normCode = 'WF-' . Str::upper(Str::random(10));

        $norm = Norm::factory()->create([
            'code' => $normCode,
            'name' => 'Norme ' . $normCode,
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $offer = Offer::factory()->create([
            'name' => 'Pack Workflow',
            'is_active' => true,
        ]);
        $offer->norms()->syncWithoutDetaching([$norm->id]);

        EnterpriseSubscription::factory()->create([
            'site_id' => $site->id,
            'offer_id' => $offer->id,
            'start_date' => now()->subDays(2),
            'expiration_date' => now()->addMonths(3),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
        ]);
    }
}
