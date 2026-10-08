<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DocumentWorkflowNotificationService
{
    /**
     * Notify verifier when document is submitted
     *
     * @param Document $document
     * @param User $verifier
     * @return void
     */
    public function notifyVerifier(Document $document, User $verifier): void
    {
        try {
            // TODO: Implement email notification
            Log::info("Notification envoyée au vérificateur", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'verifier_id' => $verifier->id,
                'verifier_email' => $verifier->email,
            ]);

            // Mail::to($verifier->email)->send(new DocumentVerificationRequested($document));
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification vérificateur", [
                'document_id' => $document->id,
                'verifier_id' => $verifier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify approver when document is verified
     *
     * @param Document $document
     * @param User $approver
     * @return void
     */
    public function notifyApprover(Document $document, User $approver): void
    {
        try {
            Log::info("Notification envoyée à l'approbateur", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'approver_id' => $approver->id,
                'approver_email' => $approver->email,
            ]);

            // Mail::to($approver->email)->send(new DocumentApprovalRequested($document));
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification approbateur", [
                'document_id' => $document->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify submitter when document is rejected
     *
     * @param Document $document
     * @param User $submitter
     * @param string $reason
     * @return void
     */
    public function notifyRejection(Document $document, User $submitter, string $reason): void
    {
        try {
            Log::info("Notification de rejet envoyée au soumetteur", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'submitter_id' => $submitter->id,
                'submitter_email' => $submitter->email,
                'reason' => $reason,
            ]);

            // Mail::to($submitter->email)->send(new DocumentRejected($document, $reason));
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification rejet", [
                'document_id' => $document->id,
                'submitter_id' => $submitter->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify submitter when document is approved
     *
     * @param Document $document
     * @param User $submitter
     * @return void
     */
    public function notifyApproval(Document $document, User $submitter): void
    {
        try {
            Log::info("Notification d'approbation envoyée au soumetteur", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'submitter_id' => $submitter->id,
                'submitter_email' => $submitter->email,
            ]);

            // Mail::to($submitter->email)->send(new DocumentApproved($document));
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification approbation", [
                'document_id' => $document->id,
                'submitter_id' => $submitter->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send reminder to verifier/approver
     *
     * @param Document $document
     * @param User $recipient
     * @param string $role (verifier|approver)
     * @return void
     */
    public function sendReminder(Document $document, User $recipient, string $role): void
    {
        try {
            Log::info("Rappel envoyé", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'role' => $role,
            ]);

            // Mail::to($recipient->email)->send(new DocumentWorkflowReminder($document, $role));
        } catch (\Exception $e) {
            Log::error("Erreur envoi rappel", [
                'document_id' => $document->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when workflow is delegated
     *
     * @param Document $document
     * @param User $delegatedTo
     * @param User $delegatedBy
     * @param string $role
     * @return void
     */
    public function notifyDelegation(Document $document, User $delegatedTo, User $delegatedBy, string $role): void
    {
        try {
            Log::info("Notification de délégation envoyée", [
                'document_id' => $document->id,
                'document_code' => $document->code,
                'delegated_to_id' => $delegatedTo->id,
                'delegated_to_email' => $delegatedTo->email,
                'delegated_by_id' => $delegatedBy->id,
                'role' => $role,
            ]);

            // Mail::to($delegatedTo->email)->send(new DocumentWorkflowDelegated($document, $delegatedBy, $role));
        } catch (\Exception $e) {
            Log::error("Erreur envoi notification délégation", [
                'document_id' => $document->id,
                'delegated_to_id' => $delegatedTo->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get pending documents for user (verifier or approver)
     *
     * @param User $user
     * @return array
     */
    public function getPendingDocuments(User $user): array
    {
        $pendingVerification = Document::where('workflow_status', 'pending_verification')
            ->where('verifier_id', $user->id)
            ->with(['author', 'site'])
            ->get();

        $pendingApproval = Document::where('workflow_status', 'pending_approval')
            ->where('approver_id', $user->id)
            ->with(['author', 'site'])
            ->get();

        return [
            'pending_verification' => $pendingVerification,
            'pending_approval' => $pendingApproval,
            'total' => $pendingVerification->count() + $pendingApproval->count(),
        ];
    }

    /**
     * Get workflow statistics for user
     *
     * @param User $user
     * @param int $days
     * @return array
     */
    public function getWorkflowStats(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $verified = Document::where('verifier_id', $user->id)
            ->where('workflow_status', '!=', 'pending_verification')
            ->where('updated_at', '>=', $startDate)
            ->count();

        $approved = Document::where('approver_id', $user->id)
            ->where('workflow_status', 'approved')
            ->where('updated_at', '>=', $startDate)
            ->count();

        $rejected = Document::where(function($query) use ($user) {
                $query->where('verifier_id', $user->id)
                      ->orWhere('approver_id', $user->id);
            })
            ->where('workflow_status', 'rejected')
            ->where('updated_at', '>=', $startDate)
            ->count();

        return [
            'verified' => $verified,
            'approved' => $approved,
            'rejected' => $rejected,
            'period_days' => $days,
        ];
    }

    /**
     * Check if document needs reminder
     *
     * @param Document $document
     * @param int $hoursThreshold
     * @return bool
     */
    public function needsReminder(Document $document, int $hoursThreshold = 48): bool
    {
        if (!in_array($document->workflow_status, ['pending_verification', 'pending_approval'])) {
            return false;
        }

        $lastUpdate = $document->updated_at;
        $hoursSinceUpdate = now()->diffInHours($lastUpdate);

        return $hoursSinceUpdate >= $hoursThreshold;
    }
}
