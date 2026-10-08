<?php

namespace App\Services;

use App\Models\DocumentSignatureWorkflow;
use App\Models\DocumentSignature;
use App\Models\User;
use App\Notifications\SignatureRequestNotification;
use App\Notifications\WorkflowCompletedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SignatureWorkflowService
{
    /**
     * Initier un workflow de signatures
     *
     * @param string $docType Type de document
     * @param int $docId ID du document
     * @param array $signers [['user_id' => 1, 'role' => 'company'], ...]
     * @param string $workflowName Nom du workflow
     */
    public function initiate(string $docType, int $docId, array $signers, string $workflowName): DocumentSignatureWorkflow
    {
        return DB::transaction(function () use ($docType, $docId, $signers, $workflowName) {
            // Créer le workflow
            $workflow = DocumentSignatureWorkflow::create([
                'document_type' => $docType,
                'document_id' => $docId,
                'workflow_name' => $workflowName,
                'total_steps' => count($signers),
                'current_step' => 1,
                'status' => 'in_progress',
                'initiated_by' => auth()->id(),
                'initiated_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

            // Créer les signatures pour chaque étape
            foreach ($signers as $index => $signer) {
                DocumentSignature::create([
                    'workflow_id' => $workflow->id,
                    'document_type' => $docType,
                    'document_id' => $docId,
                    'user_id' => $signer['user_id'],
                    'signature_order' => $index + 1,
                    'role_required' => $signer['role'] ?? null,
                    'status' => 'pending',
                    'signature_text' => "Signature requise pour {$signer['role']}",
                ]);
            }

            // Notifier le premier signataire
            $this->notifyNextSigner($workflow);

            return $workflow;
        });
    }

    /**
     * Signer un document dans le workflow
     */
    public function sign(DocumentSignatureWorkflow $workflow, User $user, string $password, ?string $comments = null): void
    {
        if (!$user->hasUploadedSignature()) {
            throw new \Exception('Vous devez importer votre signature avant de signer un document.');
        }

        if (!$workflow->canSign($user)) {
            throw new \Exception('Vous n\'êtes pas autorisé à signer ce document à cette étape.');
        }

        $signature = $workflow->currentSignature;

        if (!$signature) {
            throw new \Exception('Aucune signature en attente trouvée.');
        }

        DB::transaction(function () use ($signature, $password, $comments, $workflow) {
            // Signer
            $signature->update(['comments' => $comments]);
            $signature->sign($password);

            // Notifier le prochain signataire si workflow pas terminé
            if ($workflow->fresh()->status === 'in_progress') {
                $this->notifyNextSigner($workflow->fresh());
            } else {
                // Workflow terminé, notifier tous les participants
                $this->notifyWorkflowCompleted($workflow->fresh());
            }
        });
    }

    /**
     * Rejeter une signature
     */
    public function reject(DocumentSignatureWorkflow $workflow, User $user, string $reason): void
    {
        if (!$workflow->canSign($user)) {
            throw new \Exception('Vous n\'êtes pas autorisé à rejeter ce document.');
        }

        $signature = $workflow->currentSignature;

        if (!$signature) {
            throw new \Exception('Aucune signature en attente trouvée.');
        }

        DB::transaction(function () use ($signature, $reason) {
            $signature->reject($reason);
        });
    }

    /**
     * Notifier le prochain signataire
     */
    public function notifyNextSigner(DocumentSignatureWorkflow $workflow): void
    {
        $nextSigner = $workflow->nextSigner();

        if ($nextSigner) {
            $nextSigner->notify(new SignatureRequestNotification($workflow));
        }
    }

    /**
     * Notifier la complétion du workflow
     */
    protected function notifyWorkflowCompleted(DocumentSignatureWorkflow $workflow): void
    {
        // Notifier tous les signataires
        $signers = $workflow->signatures()->with('company')->get()->pluck('company')->unique('id');

        foreach ($signers as $signer) {
            $signer->notify(new WorkflowCompletedNotification($workflow));
        }

        // Notifier l'initiateur
        if ($workflow->initiator) {
            $workflow->initiator->notify(new WorkflowCompletedNotification($workflow));
        }
    }

    /**
     * Vérifier et expirer les workflows en retard
     */
    public function checkExpired(): Collection
    {
        $expiredWorkflows = DocumentSignatureWorkflow::expired()->get();

        foreach ($expiredWorkflows as $workflow) {
            $workflow->expire();
        }

        return $expiredWorkflows;
    }

    /**
     * Relancer un signataire en retard
     */
    public function remind(DocumentSignatureWorkflow $workflow): void
    {
        $nextSigner = $workflow->nextSigner();

        if ($nextSigner && $workflow->status === 'in_progress') {
            $nextSigner->notify(new SignatureRequestNotification($workflow, true));
        }
    }
}
