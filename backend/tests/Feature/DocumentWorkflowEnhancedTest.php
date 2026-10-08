<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentWorkflowHistory;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentWorkflowEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $verifier;
    private User $approver;
    private Site $site;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureMfaStepUp::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            'mfa.stepup',
            'check.subscription',
        ]);

        $this->site = Site::factory()->create();
        
        $this->user = User::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->verifier = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $this->verifier->givePermissionTo('verify_documents');

        $this->approver = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $this->approver->givePermissionTo('approve_documents');

        $this->document = Document::factory()->create([
            'site_id' => $this->site->id,
            'author_id' => $this->user->id,
            'workflow_status' => 'pending_verification',
        ]);
    }

    /** @test */
    public function it_logs_workflow_history_on_verification()
    {
        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/verify", [
            'comment' => 'Document vérifié avec succès',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('document_workflow_history', [
            'document_id' => $this->document->id,
            'user_id' => $this->verifier->id,
            'action' => 'verified',
            'from_status' => 'pending_verification',
            'to_status' => 'pending_approval',
            'comment' => 'Document vérifié avec succès',
        ]);
    }

    /** @test */
    public function it_logs_workflow_history_on_approval()
    {
        $this->document->update(['workflow_status' => 'pending_approval']);
        $this->actingAs($this->approver);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/approve", [
            'comment' => 'Document approuvé',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('document_workflow_history', [
            'document_id' => $this->document->id,
            'user_id' => $this->approver->id,
            'action' => 'approved',
            'from_status' => 'pending_approval',
            'to_status' => 'approved',
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
            'code_status' => 'active',
        ]);
    }

    /** @test */
    public function it_logs_workflow_history_on_rejection()
    {
        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/reject", [
            'rejection_reason' => 'Document incomplet',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('document_workflow_history', [
            'document_id' => $this->document->id,
            'user_id' => $this->verifier->id,
            'action' => 'rejected',
            'comment' => 'Document incomplet',
        ]);
    }

    /** @test */
    public function it_retrieves_workflow_history()
    {
        DocumentWorkflowHistory::factory()->count(3)->create([
            'document_id' => $this->document->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/v1/documents/{$this->document->id}/workflow-history");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_delegates_document_verification()
    {
        $delegatedUser = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $delegatedUser->givePermissionTo('verify_documents');

        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/delegate", [
            'delegated_to' => $delegatedUser->id,
            'comment' => 'Délégation pour expertise technique',
        ]);

        $response->assertStatus(200);

        $this->document->refresh();
        $this->assertEquals($delegatedUser->id, $this->document->verifier_id);

        $this->assertDatabaseHas('document_workflow_history', [
            'document_id' => $this->document->id,
            'user_id' => $this->verifier->id,
            'action' => 'delegated',
            'delegated_to' => $delegatedUser->id,
            'comment' => 'Délégation pour expertise technique',
        ]);
    }

    /** @test */
    public function it_prevents_delegation_to_user_without_permission()
    {
        $delegatedUser = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        // No permission given

        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/delegate", [
            'delegated_to' => $delegatedUser->id,
            'comment' => 'Tentative de délégation',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_delegates_document_approval()
    {
        $this->document->update(['workflow_status' => 'pending_approval']);
        
        $delegatedUser = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $delegatedUser->givePermissionTo('approve_documents');

        $this->actingAs($this->approver);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/delegate", [
            'delegated_to' => $delegatedUser->id,
            'comment' => 'Délégation approbation',
        ]);

        $response->assertStatus(200);

        $this->document->refresh();
        $this->assertEquals($delegatedUser->id, $this->document->approver_id);
    }

    /** @test */
    public function it_sends_reminder_for_pending_verification()
    {
        $this->document->update(['verifier_id' => $this->verifier->id]);
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/send-reminder");

        $response->assertStatus(200);

        $this->assertDatabaseHas('document_workflow_history', [
            'document_id' => $this->document->id,
            'action' => 'reminded',
        ]);
    }

    /** @test */
    public function it_prevents_reminder_for_non_pending_document()
    {
        $this->document->update(['workflow_status' => 'approved']);
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/send-reminder");

        $response->assertStatus(400);
    }

    /** @test */
    public function it_retrieves_pending_documents_for_verifier()
    {
        Document::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'workflow_status' => 'pending_verification',
            'verifier_id' => $this->verifier->id,
        ]);

        Document::factory()->count(2)->create([
            'site_id' => $this->site->id,
            'workflow_status' => 'pending_approval',
            'approver_id' => $this->verifier->id,
        ]);

        $this->actingAs($this->verifier);

        $response = $this->getJson('/api/v1/workflow/pending-documents');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'pending_verification',
                    'pending_approval',
                    'total',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data['pending_verification']);
        $this->assertCount(2, $data['pending_approval']);
        $this->assertEquals(5, $data['total']);
    }

    /** @test */
    public function it_retrieves_workflow_statistics()
    {
        // Create verified documents
        Document::factory()->count(5)->create([
            'site_id' => $this->site->id,
            'verifier_id' => $this->verifier->id,
            'workflow_status' => 'pending_approval',
        ]);

        // Create approved documents
        Document::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'approver_id' => $this->verifier->id,
            'workflow_status' => 'approved',
        ]);

        $this->actingAs($this->verifier);

        $response = $this->getJson('/api/v1/workflow/stats?days=30');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'verified',
                    'approved',
                    'rejected',
                    'period_days',
                ],
            ]);
    }

    /** @test */
    public function it_requires_comment_for_rejection()
    {
        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/reject", [
            'rejection_reason' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    }

    /** @test */
    public function it_requires_comment_for_delegation()
    {
        $delegatedUser = User::factory()->create([
            'site_id' => $this->site->id,
        ]);
        $delegatedUser->givePermissionTo('verify_documents');

        $this->actingAs($this->verifier);

        $response = $this->postJson("/api/v1/documents/{$this->document->id}/delegate", [
            'delegated_to' => $delegatedUser->id,
            'comment' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    /** @test */
    public function it_includes_user_relations_in_workflow_history()
    {
        DocumentWorkflowHistory::factory()->create([
            'document_id' => $this->document->id,
            'user_id' => $this->verifier->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/v1/documents/{$this->document->id}/workflow-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'comment',
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_prevents_unauthorized_access_to_workflow_history()
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $response = $this->getJson("/api/v1/documents/{$this->document->id}/workflow-history");

        $response->assertStatus(403);
    }
}
