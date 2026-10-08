<?php

namespace App\Modules\Support\Controllers;

use App\Services\DocumentApprovalFinalizer;
use App\Services\QRCodeService;

use App\Http\Controllers\Controller;
use App\Events\DocumentWorkflowEvent;
use App\Models\Document;
use App\Models\DocumentWorkflowHistory;
use App\Models\SecurityAuditLog;
use App\Models\Site;
use App\Models\User;
use App\Notifications\Document\DocumentWorkflowNotification;
use App\Services\DocumentWorkflowAuditTrailService;
use App\Services\DocumentCodeRecyclingService;
use App\Services\DocumentWorkflowNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentWorkflowController extends Controller
{
    public function __construct(
        private DocumentWorkflowNotificationService $notificationService
    ) {}

    private const WORKFLOW_TRANSITIONS = [
        'draft' => ['pending_verification', 'pending_approval'],
        'pending_verification' => ['pending_approval', 'awaiting_submitter_confirmation'],
        'pending_approval' => ['approved', 'awaiting_submitter_confirmation'],
        'awaiting_submitter_confirmation' => ['rejected'],
        'rejected' => ['pending_verification', 'pending_approval'],
        'approved' => [],
    ];

    /**
     * Confirme le code généré et lance le workflow.
     */
    public function confirmCode(Request $request, int $id)
    {
        $user = $request->user();
        $document = Document::findOrFail((int) $id);

        // **SECURITY FIX**: Vérifier que l'auteur du document est l'utilisateur courant (IDOR prevention)
        abort_unless($user && $document->author_id === $user->id, 403, 'Vous ne pouvez pas confirmer ce document.');
        
        // **SECURITY FIX**: Vérifier que le document appartient au site de l'utilisateur (tenant isolation)
        abort_unless($user && $document->site_id === $user->site_id, 403, 'Acces non autorise.');

        $request->validate([
            'needs_verification' => 'required|boolean',
            'confirmed_code' => 'required|string|max:255',
        ]);

        if ((string) $document->code !== (string) $request->string('confirmed_code')) {
            throw ValidationException::withMessages([
                'confirmed_code' => ['Le code confirme ne correspond pas au code attendu.'],
            ]);
        }

        $fromState = (string) ($document->workflow_status ?: 'draft');
        $nextState = $request->boolean('needs_verification')
            ? 'pending_verification'
            : 'pending_approval';

        $this->transitionDocument($document, $nextState, [
            'needs_verification' => $request->boolean('needs_verification'),
            'rejection_reason' => null,
        ]);

        $document->refresh();
        $this->notifyForWorkflowState($document, $user);
        event(new DocumentWorkflowEvent($document, 'submitted_for_verification', $user));
        $this->logWorkflowSecurityEvent($document, $fromState, $nextState, 'confirm_code');
        $this->logImmutableWorkflowEvent($document, 'confirm_code', $fromState, $nextState, $user?->id, [
            'needs_verification' => $request->boolean('needs_verification'),
        ]);

        return response()->json(['message' => 'Code confirmé, document en cours de traitement.', 'data' => $document]);
    }

    /**
     * Valide l'étape de vérification.
     */
    public function verify(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless($user?->can('verify_documents'), 403);
        $document = $this->findDocumentForUser((int) $id, $user);
        
        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);
        
        $fromState = (string) ($document->workflow_status ?: 'draft');
        $toState = 'pending_approval';

        $this->transitionDocument($document, $toState, [
            'verifier_id' => $user?->id,
            'verified_by' => $user?->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        $document->refresh();
        
        // Log workflow history
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'verified',
            fromStatus: $fromState,
            toStatus: $toState,
            comment: $request->input('comment')
        );
        
        // Send confirmation email to verifier
        $user->notify(new DocumentWorkflowNotification('verification_completed', $document, $user, ['outcome' => 'verified']));
        
        // Send notification to author that verification passed
        if ($document->author) {
            $document->author->notify(new DocumentWorkflowNotification('verification_completed', $document, $user, ['outcome' => 'verified']));
        }
        
        // Notify approvers that document is pending approval
        $this->notifyForWorkflowState($document, $user);
        event(new DocumentWorkflowEvent($document, 'verified', $user));
        $this->notifyPeerValidatorsOfCompletion($document, $user, 'verify_documents', 'verification_completed', 'verified');
        $this->logWorkflowSecurityEvent($document, $fromState, $toState, 'verify');
        $this->logImmutableWorkflowEvent($document, 'verify', $fromState, $toState, $user?->id);

        return response()->json(['message' => 'Document vérifié avec succès.', 'data' => $document]);
    }

    /**
     * Valide l'étape d'approbation.
     */
    public function approve(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless($user?->can('approve_documents'), 403);
        $document = $this->findDocumentForUser((int) $id, $user);
        
        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);
        
        $fromState = (string) ($document->workflow_status ?: 'draft');
        $toState = 'approved';
        $approvalAttributes = [
            'approver_id' => $user?->id,
            'status' => 'approved',
            'code_status' => 'active',
            'approved_at' => now(),
            'rejection_reason' => null,
        ];
        $versioning = (array) data_get($document->metadata, 'versioning', []);
        $baseApprovedVersion = $versioning['base_approved_version'] ?? null;

        if ($baseApprovedVersion) {
            $nextVersion = $this->incrementMajorVersion((string) $baseApprovedVersion);
            $metadata = (array) ($document->metadata ?? []);
            $metadata['versioning'] = array_merge($versioning, [
                'approved_from_version' => $baseApprovedVersion,
                'approved_as_version' => $nextVersion,
                'approved_at' => now()->toISOString(),
            ]);
            unset($metadata['versioning']['base_approved_version']);

            $approvalAttributes['version'] = $nextVersion;
            $approvalAttributes['metadata'] = $metadata;
        }

        $this->transitionDocument($document, $toState, $approvalAttributes);

        $document->refresh();
        if (!empty($nextVersion)) {
            $document->currentVersion()->update(['version_number' => $nextVersion]);
        }

        // Régénérer le PDF sans filigrane BROUILLON
        app(\App\Services\DocumentApprovalFinalizer::class)->finalize($document);

        // Révoquer l'ancien QR code et en générer un lié à la version approuvée
        app(\App\Services\QRCodeService::class)->generateApproved($document);

        if ($document->code) {
            $obsoleteIds = \App\Models\Document::query()
                ->where('site_id', $document->site_id)
                ->where('code', $document->code)
                ->where('id', '!=', $document->id)
                ->where('status', 'approved')
                ->pluck('id');

            \App\Models\Document::query()->whereIn('id', $obsoleteIds)->update(['status' => 'obsolete']);

            foreach ($obsoleteIds as $obsoleteId) {
                \App\Jobs\GenerateObsoleteWatermarkJob::dispatch($obsoleteId);
            }
        }
        
        // Log workflow history
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'approved',
            fromStatus: $fromState,
            toStatus: $toState,
            comment: $request->input('comment')
        );
        
        $this->notifyForWorkflowState($document, $user);
        event(new DocumentWorkflowEvent($document, 'approved', $user));
        $this->notifyPeerValidatorsOfCompletion($document, $user, 'approve_documents', 'approval_completed', 'approved');
        $this->logWorkflowSecurityEvent($document, $fromState, $toState, 'approve');
        $this->logImmutableWorkflowEvent($document, 'approve', $fromState, $toState, $user?->id);

        return response()->json(['message' => 'Document approuvé avec succès.', 'data' => $document]);
    }

    /**
     * Rejette le document (Vérification ou Approbation).
     */
    public function reject(Request $request, int $id)
    {
        $user = $request->user();
        $document = $this->findDocumentForUser((int) $id, $user);

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $canReject = $user?->can('verify_documents') || $user?->can('approve_documents');
        abort_unless($canReject, 403);

        $fromState = (string) ($document->workflow_status ?: 'draft');
        $toState = 'awaiting_submitter_confirmation';
        $existingMetadata = (array) ($document->metadata ?? []);
        $existingRejection = (array) ($existingMetadata['rejection'] ?? []);

        $this->transitionDocument($document, $toState, [
            'rejection_reason' => $request->input('rejection_reason'),
            'status' => 'draft',
            'metadata' => array_merge(
                $existingMetadata,
                [
                    'rejection' => array_merge(
                        $existingRejection,
                        [
                            'requested_by' => $user?->id,
                            'requested_at' => now()->toISOString(),
                            'awaiting_submitter_confirmation' => true,
                            'submitter_decision' => null,
                            'submitter_decision_at' => null,
                            'code_released' => false,
                        ]
                    ),
                ],
            ),
        ]);

        $document->refresh();
        
        // Log workflow history
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'rejected',
            fromStatus: $fromState,
            toStatus: $toState,
            comment: $request->input('rejection_reason')
        );
        
        $this->notifyForWorkflowState($document, $user);
        event(new DocumentWorkflowEvent($document, 'rejected', $user, $request->input('rejection_reason')));
        if ($fromState === 'pending_verification') {
            $this->notifyPeerValidatorsOfCompletion($document, $user, 'verify_documents', 'verification_completed', 'rejected');
        } elseif ($fromState === 'pending_approval') {
            $this->notifyPeerValidatorsOfCompletion($document, $user, 'approve_documents', 'approval_completed', 'rejected');
        }
        $this->logWorkflowSecurityEvent($document, $fromState, $toState, 'reject');
        $this->logImmutableWorkflowEvent($document, 'reject', $fromState, $toState, $user?->id, [
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'message' => 'Document rejete. En attente de confirmation du soumissionnaire pour la liberation du code.',
            'data' => $document,
        ]);
    }

    /**
     * Confirme la decision du soumissionnaire apres un rejet.
     */
    public function confirmRejectionDecision(Request $request, int $id)
    {
        $user = $request->user();
        $document = $this->findDocumentForUser((int) $id, $user);

        $request->validate([
            'release_code' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ((int) $document->author_id !== (int) $user?->id) {
            return response()->json([
                'message' => 'Seul le soumissionnaire peut confirmer la decision apres rejet.',
            ], 403);
        }

        $fromState = (string) ($document->workflow_status ?: 'draft');
        $toState = 'rejected';
        $releaseCode = (bool) $request->boolean('release_code');
        $metadata = (array) ($document->metadata ?? []);
        $rejection = (array) ($metadata['rejection'] ?? []);
        $rejection['awaiting_submitter_confirmation'] = false;
        $rejection['submitter_decision'] = $releaseCode ? 'release_code' : 'keep_code';
        $rejection['submitter_decision_at'] = now()->toISOString();
        $rejection['submitter_comment'] = $request->input('comment');
        $rejection['code_released'] = $releaseCode;
        $metadata['rejection'] = $rejection;

        $this->transitionDocument($document, $toState, [
            'metadata' => $metadata,
            'code_status' => $releaseCode ? 'released' : 'reserved',
        ]);

        app(DocumentCodeRecyclingService::class)->handleRejectionDecision(
            documentId: $document->id,
            releaseCode: $releaseCode
        );

        $document->refresh();
        
        // Log workflow history
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'rejection_confirmed',
            fromStatus: $fromState,
            toStatus: $toState,
            comment: $request->input('comment'),
            metadata: ['release_code' => $releaseCode]
        );
        
        $this->logWorkflowSecurityEvent($document, $fromState, $toState, 'confirm_rejection_decision', [
            'release_code' => $releaseCode,
        ]);
        $this->logImmutableWorkflowEvent($document, 'confirm_rejection_decision', $fromState, $toState, $user?->id, [
            'release_code' => $releaseCode,
        ]);

        return response()->json([
            'message' => $releaseCode
                ? 'Decision enregistree. Le code est libere et disponible pour reutilisation.'
                : 'Decision enregistree. Le code reste reserve.',
            'data' => $document,
        ]);
    }

    /**
     * Get workflow history for a document
     */
    public function getHistory(Request $request, int $id)
    {
        $user = $request->user();
        $document = $this->findDocumentForUser((int) $id, $user);

        $history = DocumentWorkflowHistory::forDocument($document->id)
            ->withRelations()
            ->orderBy('action_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Retourne le dernier document généré pour une source métier.
     */
    public function generatedSourceState(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'source_type' => 'required|string|max:100',
            'source_id' => 'required|string|max:100',
            'document_kind' => 'nullable|string|max:100',
            'source_updated_at' => 'nullable|date',
            'site_id' => 'nullable|integer',
        ]);

        $query = Document::query()
            ->where('metadata->source', 'generated_process_document')
            ->where('metadata->source_type', $validated['source_type'])
            ->where('metadata->source_model_id', (string) $validated['source_id']);

        if (!empty($validated['document_kind'])) {
            $query->where('metadata->document_kind', $validated['document_kind']);
        }

        if (!empty($validated['site_id'])) {
            $query->where('site_id', (int) $validated['site_id']);
        }

        if (!$user->isSuperAdmin()) {
            $query->where(function ($scope) use ($user) {
                if (!empty($user->site_id)) {
                    $scope->where('site_id', (int) $user->site_id);
                }

                if (!empty($user->enterprise_id)) {
                    $scope->orWhereHas('site', function ($siteQuery) use ($user) {
                        $siteQuery->where('enterprise_id', (int) $user->enterprise_id);
                    });
                }
            });
        }

        $document = $query->latest('id')->first();
        $sourceDirty = false;

        if ($document && !empty($validated['source_updated_at'])) {
            $sourceUpdatedAt = \Carbon\Carbon::parse($validated['source_updated_at']);
            $baseline = $document->created_at;
            $sourceExportedAt = data_get($document->metadata, 'source_updated_at');
            if ($sourceExportedAt) {
                $baseline = \Carbon\Carbon::parse($sourceExportedAt);
            }
            $sourceDirty = $sourceUpdatedAt->greaterThan($baseline);
        }

        $workflowStatus = $document?->workflow_status ?: null;
        $isSubmitted = $document
            ? in_array($workflowStatus, ['pending_verification', 'pending_approval', 'approved'], true)
            : false;

        return response()->json([
            'data' => [
                'document' => $document ? [
                    'id' => $document->id,
                    'code' => $document->code,
                    'status' => $document->status,
                    'workflow_status' => $workflowStatus,
                    'created_at' => $document->created_at?->toISOString(),
                    'updated_at' => $document->updated_at?->toISOString(),
                ] : null,
                'has_export' => (bool) $document,
                'is_submitted' => $isSubmitted,
                'source_dirty' => $sourceDirty,
                'can_submit_existing_export' => (bool) (
                    $document
                    && !$sourceDirty
                    && in_array($workflowStatus, ['draft', 'rejected'], true)
                ),
                'requires_new_export' => (bool) ($sourceDirty && $document),
            ],
        ]);
    }

    /**
     * Delegate verification or approval to another user
     */
    public function delegate(Request $request, int $id)
    {
        $user = $request->user();
        $document = $this->findDocumentForUser((int) $id, $user);

        $request->validate([
            'delegated_to' => 'required|exists:users,id',
            'comment' => 'required|string|max:1000',
        ]);

        $delegatedTo = User::findOrFail($request->input('delegated_to'));

        // Verify delegated user has appropriate permission
        if ($document->workflow_status === 'pending_verification') {
            abort_unless($delegatedTo->can('verify_documents'), 400, 'L\'utilisateur délégué doit avoir la permission de vérifier les documents.');
            $document->verifier_id = $delegatedTo->id;
        } elseif ($document->workflow_status === 'pending_approval') {
            abort_unless($delegatedTo->can('approve_documents'), 400, 'L\'utilisateur délégué doit avoir la permission d\'approuver les documents.');
            $document->approver_id = $delegatedTo->id;
        } else {
            abort(400, 'Le document doit être en attente de vérification ou d\'approbation pour être délégué.');
        }

        $document->save();

        // Log delegation
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'delegated',
            fromStatus: $document->workflow_status,
            toStatus: $document->workflow_status,
            comment: $request->input('comment'),
            delegatedTo: $delegatedTo->id
        );

        // Notify delegated user
        $role = $document->workflow_status === 'pending_verification' ? 'verifier' : 'approver';
        $this->notificationService->notifyDelegation($document, $delegatedTo, $user, $role);

        return response()->json([
            'message' => 'Document délégué avec succès.',
            'data' => $document,
        ]);
    }

    /**
     * Send reminder to verifier or approver
     */
    public function sendReminder(Request $request, int $id)
    {
        $user = $request->user();
        $document = $this->findDocumentForUser((int) $id, $user);

        if (!in_array($document->workflow_status, ['pending_verification', 'pending_approval'])) {
            return response()->json([
                'message' => 'Le document doit être en attente de vérification ou d\'approbation.',
            ], 400);
        }

        $recipient = null;
        $role = null;

        if ($document->workflow_status === 'pending_verification' && $document->verifier_id) {
            $recipient = User::find($document->verifier_id);
            $role = 'verifier';
        } elseif ($document->workflow_status === 'pending_approval' && $document->approver_id) {
            $recipient = User::find($document->approver_id);
            $role = 'approver';
        }

        if (!$recipient) {
            return response()->json([
                'message' => 'Aucun destinataire trouvé pour le rappel.',
            ], 400);
        }

        // Log reminder
        DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $user->id,
            action: 'reminded',
            fromStatus: $document->workflow_status,
            toStatus: $document->workflow_status,
            metadata: ['recipient_id' => $recipient->id, 'role' => $role]
        );

        // Send reminder notification
        $this->notificationService->sendReminder($document, $recipient, $role);

        return response()->json([
            'message' => 'Rappel envoyé avec succès.',
        ]);
    }

    /**
     * Get pending documents for current user
     */
    public function getPendingDocuments(Request $request)
    {
        $user = $request->user();
        $pending = $this->notificationService->getPendingDocuments($user);

        return response()->json([
            'success' => true,
            'data' => $pending,
        ]);
    }

    /**
     * Get workflow statistics for current user
     */
    public function getWorkflowStats(Request $request)
    {
        $user = $request->user();
        $days = $request->input('days', 30);
        $stats = $this->notificationService->getWorkflowStats($user, $days);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    private function findDocumentForUser(int $documentId, ?User $user): Document
    {
        $document = Document::with('site')->findOrFail($documentId);

        if (!$user || $user->user_type === 'super_admin') {
            return $document;
        }

        if (!empty($user->site_id) && (int) $document->site_id === (int) $user->site_id) {
            return $document;
        }

        $site = $document->site ?? Site::query()->find($document->site_id);
        if (!$site || (int) $site->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        return $document;
    }

    private function transitionDocument(Document $document, string $targetState, array $attributes = []): void
    {
        $currentState = (string) ($document->workflow_status ?: 'draft');
        $this->assertAllowedTransition($currentState, $targetState);

        $updated = DB::transaction(function () use ($document, $currentState, $targetState, $attributes) {
            $query = Document::query()->whereKey($document->id);
            if ($currentState === 'draft') {
                $query->where(function ($inner) {
                    $inner->where('workflow_status', 'draft')
                        ->orWhereNull('workflow_status');
                });
            } else {
                $query->where('workflow_status', $currentState);
            }

            return $query->update(array_merge($attributes, [
                'workflow_status' => $targetState,
                'updated_at' => now(),
            ]));
        });

        if ($updated === 0) {
            abort(409, 'Le statut du workflow a change. Rechargez la page et reessayez.');
        }
    }

    private function assertAllowedTransition(string $fromState, string $toState): void
    {
        $allowed = self::WORKFLOW_TRANSITIONS[$fromState] ?? [];
        if (!in_array($toState, $allowed, true)) {
            abort(400, "Transition invalide: {$fromState} -> {$toState}");
        }
    }

    private function incrementMajorVersion(string $version): string
    {
        if (preg_match('/^(\d+)(?:\.\d+)?$/', trim($version), $matches)) {
            return ((int) $matches[1] + 1) . '.0';
        }

        return '2.0';
    }

    private function notifyForWorkflowState(Document $document, ?User $actor): void
    {
        if ($document->workflow_status === 'pending_verification') {
            $verifiers = $this->findWorkflowUsersByPermission('verify_documents', $document, $actor?->id);
            \Illuminate\Support\Facades\Log::info('Notification: Sending verification requests', [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'verifier_count' => $verifiers->count(),
                'verifier_ids' => $verifiers->pluck('id')->toArray(),
            ]);
            foreach ($verifiers as $verifier) {
                $verifier->notify(new DocumentWorkflowNotification('verification_request', $document, $actor));
                \Illuminate\Support\Facades\Log::debug('✅ Verification notification sent', ['verifier_id' => $verifier->id, 'verifier_email' => $verifier->email]);
            }

            return;
        }

        if ($document->workflow_status === 'pending_approval') {
            $approvers = $this->findWorkflowUsersByPermission('approve_documents', $document, $actor?->id);
            \Illuminate\Support\Facades\Log::info('Notification: Sending approval requests', [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'approver_count' => $approvers->count(),
                'approver_ids' => $approvers->pluck('id')->toArray(),
            ]);
            foreach ($approvers as $approver) {
                $approver->notify(new DocumentWorkflowNotification('approval_request', $document, $actor));
                \Illuminate\Support\Facades\Log::debug('✅ Approval notification sent', ['approver_id' => $approver->id, 'approver_email' => $approver->email]);
            }

            return;
        }

        if ($document->workflow_status === 'awaiting_submitter_confirmation' && $document->author) {
            \Illuminate\Support\Facades\Log::info('Notification: Sending rejection decision required', [
                'document_id' => $document->id,
                'author_id' => $document->author->id,
                'author_email' => $document->author->email,
            ]);
            $document->author->notify(new DocumentWorkflowNotification('rejection_decision_required', $document, $actor));
            return;
        }

        if ($document->workflow_status === 'approved' && $document->author) {
            \Illuminate\Support\Facades\Log::info('Notification: Sending publication notification', [
                'document_id' => $document->id,
                'author_id' => $document->author->id,
                'author_email' => $document->author->email,
            ]);
            $document->author->notify(new DocumentWorkflowNotification('publication', $document, $actor));
        }
    }

    private function findWorkflowUsersByPermission(string $permission, Document $document, ?int $excludeUserId = null)
    {
        $query = User::query()
            ->where('is_active', true)
            ->permission($permission);

        if ($document->site_id) {
            $query->where('site_id', $document->site_id);
        } elseif ($document->site?->enterprise_id) {
            $query->where('enterprise_id', $document->site->enterprise_id);
        }

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $results = $query->get();

        // Fallback : si aucun destinataire trouvé, inclure l'auteur s'il a la permission
        if ($results->isEmpty() && $excludeUserId) {
            $author = User::query()
                ->where('id', $excludeUserId)
                ->where('is_active', true)
                ->permission($permission)
                ->first();
            if ($author) {
                $results = collect([$author]);
            }
        }

        return $results;
    }

    private function notifyPeerValidatorsOfCompletion(
        Document $document,
        ?User $actor,
        string $permission,
        string $workflowType,
        string $outcome
    ): void {
        $peers = $this->findWorkflowUsersByPermission($permission, $document, $actor?->id);
        foreach ($peers as $peer) {
            $peer->notify(new DocumentWorkflowNotification($workflowType, $document, $actor, [
                'outcome' => $outcome,
            ]));
        }
    }

    private function logWorkflowSecurityEvent(
        Document $document,
        string $fromState,
        string $toState,
        string $action,
        array $extraMetadata = []
    ): void {
        SecurityAuditLog::logEvent(
            eventType: 'document_workflow_transition',
            action: $action,
            resourceType: 'document',
            resourceId: $document->id,
            metadata: array_merge([
                'document_id' => $document->id,
                'site_id' => $document->site_id,
                'from_state' => $fromState,
                'to_state' => $toState,
            ], $extraMetadata),
            riskLevel: in_array($action, ['approve', 'reject'], true) ? 'high' : 'medium',
        );
    }

    private function logImmutableWorkflowEvent(
        Document $document,
        string $eventType,
        ?string $fromState,
        string $toState,
        ?int $actorUserId,
        array $metadata = []
    ): void {
        app(DocumentWorkflowAuditTrailService::class)->recordEvent(
            document: $document,
            eventType: $eventType,
            fromStatus: $fromState,
            toStatus: $toState,
            actorUserId: $actorUserId,
            metadata: $metadata,
        );
    }
}
