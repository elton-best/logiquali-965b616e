<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Document;
use App\Models\DocumentCodePool;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use App\Notifications\Document\DocumentWorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentWorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate workflow security behavior from global blocking middleware (423).
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
        ]);

        Permission::findOrCreate('verify_documents', 'web');
        Permission::findOrCreate('approve_documents', 'web');
    }

    public function test_verify_requires_permission(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();

        $response = $this
            ->actingAs($user)
            ->postJson("/api/v1/documents/{$document->id}/verify");

        $response->assertStatus(403);
    }

    public function test_verify_is_denied_for_other_tenant_even_with_permission(): void
    {
        [$user] = $this->makeUserAndDocumentSameTenant();
        $otherDocument = $this->makeDocumentForAnotherTenant();

        Permission::findOrCreate('verify_documents', 'web');
        $user->givePermissionTo('verify_documents');

        $response = $this
            ->actingAs($user)
            ->postJson("/api/v1/documents/{$otherDocument->id}/verify");

        $response->assertStatus(403);
    }

    public function test_reject_never_force_deletes_document(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();

        Permission::findOrCreate('approve_documents', 'web');
        $user->givePermissionTo('approve_documents');

        $response = $this
            ->actingAs($user)
            ->postJson("/api/v1/documents/{$document->id}/reject", [
                'rejection_reason' => 'Incoherence de contenu',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'workflow_status' => 'awaiting_submitter_confirmation',
        ]);
        $this->assertDatabaseHas('document_workflow_events', [
            'document_id' => $document->id,
            'event_type' => 'reject',
        ]);
    }

    public function test_approve_fails_when_transition_is_invalid(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update(['workflow_status' => 'draft', 'status' => 'draft']);

        Permission::findOrCreate('approve_documents', 'web');
        $user->givePermissionTo('approve_documents');

        $response = $this
            ->actingAs($user)
            ->postJson("/api/v1/documents/{$document->id}/approve");

        $response->assertStatus(400);
    }

    public function test_only_submitter_can_confirm_rejection_decision(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();

        $otherUser = User::factory()->create([
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $user->site_id,
            'user_type' => 'company',
        ]);

        Permission::findOrCreate('approve_documents', 'web');
        $user->givePermissionTo('approve_documents');
        $this->actingAs($user)->postJson("/api/v1/documents/{$document->id}/reject", [
            'rejection_reason' => 'Non conforme',
        ])->assertOk();

        $response = $this
            ->actingAs($otherUser)
            ->postJson("/api/v1/documents/{$document->id}/confirm-rejection-decision", [
                'release_code' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_workflow_integrity_endpoint_returns_valid_chain(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();
        Permission::findOrCreate('verify_documents', 'web');
        $user->givePermissionTo('verify_documents');

        $this->actingAs($user)->postJson("/api/v1/documents/{$document->id}/verify")->assertOk();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/v1/documents/{$document->id}/workflow-integrity");

        $response->assertOk();
        $response->assertJsonPath('data.valid', true);
    }

    public function test_verify_notifies_other_verifiers_that_step_is_already_closed(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();
        Permission::findOrCreate('verify_documents', 'web');
        $user->givePermissionTo('verify_documents');

        $peerVerifier = User::factory()->create([
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $user->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $peerVerifier->givePermissionTo('verify_documents');

        Notification::fake();

        $this->actingAs($user)
            ->postJson("/api/v1/documents/{$document->id}/verify")
            ->assertOk();

        Notification::assertSentTo(
            $peerVerifier,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($peerVerifier): bool {
                $payload = $notification->toArray($peerVerifier);
                return $payload['workflow_type'] === 'verification_completed'
                    && ($payload['context']['outcome'] ?? null) === 'verified';
            }
        );
    }

    public function test_reject_notifies_other_approvers_that_step_is_already_closed(): void
    {
        [$user, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'pending_approval',
            'status' => 'pending_approval',
        ]);
        Permission::findOrCreate('approve_documents', 'web');
        $user->givePermissionTo('approve_documents');

        $peerApprover = User::factory()->create([
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $user->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $peerApprover->givePermissionTo('approve_documents');

        Notification::fake();

        $this->actingAs($user)
            ->postJson("/api/v1/documents/{$document->id}/reject", [
                'rejection_reason' => 'Contenu non conforme',
            ])
            ->assertOk();

        Notification::assertSentTo(
            $peerApprover,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($peerApprover): bool {
                $payload = $notification->toArray($peerApprover);
                return $payload['workflow_type'] === 'approval_completed'
                    && ($payload['context']['outcome'] ?? null) === 'rejected';
            }
        );
    }

    public function test_verifier_rejection_requires_submitter_decision_and_notifies_submitter(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $verifier = User::factory()->create([
            'enterprise_id' => $author->enterprise_id,
            'site_id' => $author->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $verifier->givePermissionTo('verify_documents');

        Notification::fake();

        $this->actingAs($verifier)
            ->postJson("/api/v1/documents/{$document->id}/reject", [
                'rejection_reason' => 'Preuve manquante',
            ])
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'workflow_status' => 'awaiting_submitter_confirmation',
            'rejection_reason' => 'Preuve manquante',
        ]);

        Notification::assertSentTo(
            $author,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification) use ($author): bool {
                $payload = $notification->toArray($author);
                return $payload['workflow_type'] === 'rejection_decision_required';
            }
        );
    }

    public function test_submit_for_verification_notifies_authorized_verifiers_on_all_channels(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'draft',
            'status' => 'draft',
            'needs_verification' => true,
        ]);

        $verifier = User::factory()->create([
            'enterprise_id' => $author->enterprise_id,
            'site_id' => $author->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $verifier->givePermissionTo('verify_documents');

        $inactiveVerifier = User::factory()->create([
            'enterprise_id' => $author->enterprise_id,
            'site_id' => $author->site_id,
            'user_type' => 'company',
            'is_active' => false,
        ]);
        $inactiveVerifier->givePermissionTo('verify_documents');

        Notification::fake();

        $this->actingAs($author)
            ->postJson("/api/v1/documents/{$document->id}/submit-for-approval")
            ->assertOk();

        Notification::assertSentTo(
            $verifier,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($verifier): bool {
                $payload = $notification->toArray($verifier);

                return $payload['workflow_type'] === 'verification_request'
                    && in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true);
            }
        );
        Notification::assertNotSentTo($inactiveVerifier, DocumentWorkflowNotification::class);
    }

    public function test_verification_notifies_approvers_and_peer_verifiers(): void
    {
        [$verifier, $document] = $this->makeUserAndDocumentSameTenant();
        $verifier->givePermissionTo('verify_documents');

        $approver = User::factory()->create([
            'enterprise_id' => $verifier->enterprise_id,
            'site_id' => $verifier->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $approver->givePermissionTo('approve_documents');

        $peerVerifier = User::factory()->create([
            'enterprise_id' => $verifier->enterprise_id,
            'site_id' => $verifier->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $peerVerifier->givePermissionTo('verify_documents');

        Notification::fake();

        $this->actingAs($verifier)
            ->postJson("/api/v1/documents/{$document->id}/verify")
            ->assertOk();

        Notification::assertSentTo(
            $approver,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($approver): bool {
                $payload = $notification->toArray($approver);

                return $payload['workflow_type'] === 'approval_request'
                    && in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true);
            }
        );

        Notification::assertSentTo(
            $peerVerifier,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($peerVerifier): bool {
                $payload = $notification->toArray($peerVerifier);

                return $payload['workflow_type'] === 'verification_completed'
                    && ($payload['context']['outcome'] ?? null) === 'verified'
                    && in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true);
            }
        );
    }

    public function test_final_approval_notifies_submitter_and_peer_approvers(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'pending_approval',
            'status' => 'pending_approval',
        ]);

        $approver = User::factory()->create([
            'enterprise_id' => $author->enterprise_id,
            'site_id' => $author->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $approver->givePermissionTo('approve_documents');

        $peerApprover = User::factory()->create([
            'enterprise_id' => $author->enterprise_id,
            'site_id' => $author->site_id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $peerApprover->givePermissionTo('approve_documents');

        Notification::fake();

        $this->actingAs($approver)
            ->postJson("/api/v1/documents/{$document->id}/approve")
            ->assertOk();

        Notification::assertSentTo(
            $author,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($author): bool {
                $payload = $notification->toArray($author);

                return $payload['workflow_type'] === 'publication'
                    && in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true);
            }
        );

        Notification::assertSentTo(
            $peerApprover,
            DocumentWorkflowNotification::class,
            function (DocumentWorkflowNotification $notification, array $channels) use ($peerApprover): bool {
                $payload = $notification->toArray($peerApprover);

                return $payload['workflow_type'] === 'approval_completed'
                    && ($payload['context']['outcome'] ?? null) === 'approved'
                    && in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true);
            }
        );
    }

    public function test_workflow_badges_count_only_documents_really_pending_by_status(): void
    {
        [$verifier, $pendingVerification] = $this->makeUserAndDocumentSameTenant();
        $verifier->givePermissionTo('verify_documents');
        $verifier->givePermissionTo('approve_documents');
        $pendingVerification->update([
            'workflow_status' => 'pending_verification',
            'code_status' => 'reserved',
            'verified_at' => null,
        ]);

        Document::factory()->create([
            'site_id' => $pendingVerification->site_id,
            'author_id' => $pendingVerification->author_id,
            'workflow_status' => 'draft',
            'code_status' => 'reserved',
            'verified_at' => null,
        ]);

        $pendingApproval = Document::factory()->create([
            'site_id' => $pendingVerification->site_id,
            'author_id' => $pendingVerification->author_id,
            'workflow_status' => 'pending_approval',
            'code_status' => 'reserved',
            'verified_at' => null,
        ]);

        Document::factory()->create([
            'site_id' => $pendingVerification->site_id,
            'author_id' => $pendingVerification->author_id,
            'workflow_status' => 'approved',
            'code_status' => 'reserved',
            'verified_at' => now(),
        ]);

        $this->actingAs($verifier)
            ->getJson('/api/v1/document-code-workflow/pending-verification')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingVerification->id);

        $this->actingAs($verifier)
            ->getJson('/api/v1/document-code-workflow/pending-approval')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingApproval->id);

        $this->actingAs($verifier)
            ->getJson('/api/v1/document-code-workflow/stats')
            ->assertOk()
            ->assertJsonPath('data.pending_verification', 1)
            ->assertJsonPath('data.pending_approval', 1);
    }

    public function test_submitter_can_keep_code_after_rejection(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'awaiting_submitter_confirmation',
            'status' => 'draft',
            'code_status' => 'reserved',
            'rejection_reason' => 'Correction demandee',
            'metadata' => [
                'rejection' => [
                    'awaiting_submitter_confirmation' => true,
                ],
            ],
        ]);

        $response = $this->actingAs($author)
            ->postJson("/api/v1/documents/{$document->id}/confirm-rejection-decision", [
                'release_code' => false,
                'comment' => 'Je garde le code et corrige le document.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.workflow_status', 'rejected');

        $document->refresh();
        $this->assertSame('keep_code', data_get($document->metadata, 'rejection.submitter_decision'));
        $this->assertFalse((bool) data_get($document->metadata, 'rejection.code_released'));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'workflow_status' => 'rejected',
            'code_status' => 'reserved',
        ]);
    }

    public function test_submitter_can_release_code_after_rejection(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'awaiting_submitter_confirmation',
            'status' => 'draft',
            'code_status' => 'reserved',
            'rejection_reason' => 'Mauvais type documentaire',
            'metadata' => [
                'rejection' => [
                    'awaiting_submitter_confirmation' => true,
                ],
            ],
        ]);
        DocumentCodePool::query()->create([
            'site_id' => $document->site_id,
            'document_type' => 'DOC',
            'code' => $document->code,
            'status' => 'used',
            'document_id' => $document->id,
            'used_at' => now(),
        ]);

        $response = $this->actingAs($author)
            ->postJson("/api/v1/documents/{$document->id}/confirm-rejection-decision", [
                'release_code' => true,
                'comment' => 'Le code peut etre reutilise.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.workflow_status', 'rejected');

        $document->refresh();
        $this->assertSame('release_code', data_get($document->metadata, 'rejection.submitter_decision'));
        $this->assertTrue((bool) data_get($document->metadata, 'rejection.code_released'));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'workflow_status' => 'rejected',
            'code_status' => 'released',
        ]);
        $this->assertDatabaseHas('document_code_pool', [
            'code' => $document->code,
            'status' => 'available',
            'document_id' => null,
            'release_reason' => 'submitter_decision_after_rejection',
        ]);
    }

    public function test_submitter_can_resubmit_after_rejection_when_code_is_kept(): void
    {
        [$author, $document] = $this->makeUserAndDocumentSameTenant();
        $document->update([
            'workflow_status' => 'awaiting_submitter_confirmation',
            'status' => 'draft',
            'needs_verification' => true,
            'code_status' => 'reserved',
            'metadata' => [
                'rejection' => [
                    'awaiting_submitter_confirmation' => true,
                ],
            ],
        ]);

        $this->actingAs($author)
            ->postJson("/api/v1/documents/{$document->id}/confirm-rejection-decision", [
                'release_code' => false,
            ])
            ->assertOk();

        $this->actingAs($author)
            ->postJson("/api/v1/documents/{$document->id}/submit-for-approval")
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'workflow_status' => 'pending_verification',
            'code_status' => 'reserved',
        ]);
    }

    private function makeUserAndDocumentSameTenant(): array
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'author_id' => $user->id,
            'workflow_status' => 'pending_verification',
            'status' => 'pending_approval',
        ]);

        return [$user, $document];
    }

    private function makeDocumentForAnotherTenant(): Document
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $author = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
        ]);

        return Document::factory()->create([
            'site_id' => $site->id,
            'author_id' => $author->id,
            'workflow_status' => 'pending_verification',
            'status' => 'pending_approval',
        ]);
    }
}
