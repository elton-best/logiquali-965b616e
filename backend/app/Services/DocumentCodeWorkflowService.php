<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentTypeConfiguration;
use App\Models\User;
use App\Notifications\Document\DocumentCodeWorkflowNotification;
use Illuminate\Support\Facades\DB;

class DocumentCodeWorkflowService
{
    public function __construct(
        private CodeGenerationService $codeGenerationService
    ) {}

    public function verifyCode(Document $document, int $verifierId): Document
    {
        if ($document->code_status !== 'reserved') {
            throw new \Exception('Seuls les codes réservés peuvent être vérifiés');
        }

        $document->update([
            'code_status' => 'reserved',
            'verified_at' => now(),
            'verified_by' => $verifierId,
            'workflow_status' => 'pending_approval',
        ]);

        // Notifier l'auteur
        if ($document->author) {
            $verifier = User::find($verifierId);
            $document->author->notify(
                new DocumentCodeWorkflowNotification('code_verified', $document, $verifier)
            );
        }

        // Notifier les approbateurs
        $approvers = $this->findUsersByPermission('approve_documents', $document, $verifierId);
        foreach ($approvers as $approver) {
            $approver->notify(
                new DocumentCodeWorkflowNotification('code_approval_request', $document, User::find($verifierId))
            );
        }

        return $document->fresh();
    }

    public function activateCode(Document $document, int $approverId): Document
    {
        if (!$document->verified_at) {
            throw new \Exception('Le code doit être vérifié avant activation');
        }

        if ($document->code_status === 'active') {
            throw new \Exception('Le code est déjà actif');
        }

        if ($document->code && $document->document_type_configuration_id) {
            $this->codeGenerationService->activateCode(
                $document->code,
                $document->document_type_configuration_id
            );
        }

        $document->update([
            'code_status' => 'active',
            'workflow_status' => 'approved',
            'status' => 'approved',
            'approved_at' => now(),
            'approver_id' => $approverId,
        ]);

        // Notifier l'auteur
        if ($document->author) {
            $approver = User::find($approverId);
            $document->author->notify(
                new DocumentCodeWorkflowNotification('code_approved', $document, $approver)
            );
        }

        return $document->fresh();
    }

    public function releaseCode(Document $document, ?int $rejectorId = null): void
    {
        if ($document->code_status === 'released') {
            return;
        }

        if ($document->document_type_configuration_id && $document->code) {
            $this->codeGenerationService->releaseCode(
                $document->code,
                $document->document_type_configuration_id
            );
        }

        $document->update(['code_status' => 'released']);

        // Notifier l'auteur du rejet
        if ($document->author && $rejectorId) {
            $rejector = User::find($rejectorId);
            $document->author->notify(
                new DocumentCodeWorkflowNotification('code_rejected', $document, $rejector)
            );
        }
    }

    public function getPendingVerification(?int $siteId = null)
    {
        $query = Document::query()
            ->with(['site', 'process', 'author', 'category', 'typeConfiguration'])
            ->where('workflow_status', 'pending_verification');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return $query->latest('id')->get();
    }

    public function getPendingApproval(?int $siteId = null)
    {
        $query = Document::query()
            ->with(['site', 'process', 'author', 'category', 'typeConfiguration', 'verifier'])
            ->where('workflow_status', 'pending_approval');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return $query->latest('verified_at')->get();
    }

    public function getWorkflowStats(?int $siteId = null): array
    {
        $query = Document::query();

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return [
            'pending_verification' => (clone $query)
                ->where('workflow_status', 'pending_verification')
                ->count(),
            'pending_approval' => (clone $query)
                ->where('workflow_status', 'pending_approval')
                ->count(),
            'active_codes' => (clone $query)
                ->where('code_status', 'active')
                ->count(),
            'released_codes' => (clone $query)
                ->where('code_status', 'released')
                ->count(),
            'total_documents' => $query->count(),
        ];
    }

    private function findUsersByPermission(string $permission, Document $document, ?int $excludeUserId = null)
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

        return $query->get();
    }
}
