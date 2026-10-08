<?php

namespace App\Listeners;

use App\Events\DocumentWorkflowEvent;
use App\Models\User;
use App\Notifications\SiteEventNotification;
use Illuminate\Support\Facades\Log;

class SendDocumentWorkflowNotification
{
    public function handle(DocumentWorkflowEvent $event): void
    {
        $recipients = $this->getRecipients($event);

        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for document workflow notification', [
                'document_id' => $event->document->id,
                'action' => $event->action,
            ]);
            return;
        }

        $notificationData = $this->buildNotificationData($event);

        foreach ($recipients as $recipient) {
            $recipient->notify(new SiteEventNotification('document_workflow', [
                'type' => 'document_workflow',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'document_id' => $event->document->id,
                'document_code' => $event->document->code,
                'action' => $event->action,
                'actor_name' => $event->actor?->name,
                'action_url' => $notificationData['action_url'],
            ], $event->actor?->id));
        }

        Log::info('Document workflow notifications sent', [
            'document_id' => $event->document->id,
            'action' => $event->action,
            'recipients_count' => $recipients->count(),
        ]);
    }

    private function getRecipients(DocumentWorkflowEvent $event): \Illuminate\Support\Collection
    {
        return match ($event->action) {
            'imported', 'submitted_for_verification' => $this->getVerifiers($event->document),
            'verified' => $this->getApprovers($event->document),
            'approved' => collect([$event->document->author]),
            'rejected' => collect([$event->document->author]),
            'code_released' => $this->getDocumentManagers($event->document),
            default => collect(),
        };
    }

    private function getVerifiers($document): \Illuminate\Support\Collection
    {
        return User::where('enterprise_id', $document->site->enterprise_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'document_verifier');
            })
            ->orWhereHas('permissions', function ($query) {
                $query->where('name', 'verify_documents');
            })
            ->get();
    }

    private function getApprovers($document): \Illuminate\Support\Collection
    {
        return User::where('enterprise_id', $document->site->enterprise_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'document_approver');
            })
            ->orWhereHas('permissions', function ($query) {
                $query->where('name', 'approve_documents');
            })
            ->get();
    }

    private function getDocumentManagers($document): \Illuminate\Support\Collection
    {
        return User::where('enterprise_id', $document->site->enterprise_id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin_entreprise', 'document_manager']);
            })
            ->get();
    }

    private function buildNotificationData(DocumentWorkflowEvent $event): array
    {
        $docTitle = $event->document->title ?? $event->document->titre ?? 'Document';
        $docCode = $event->document->code ?? 'N/A';
        $actorName = $event->actor?->name ?? 'Système';

        return match ($event->action) {
            'imported' => [
                'title' => 'Nouveau document à vérifier',
                'message' => "{$actorName} a importé le document \"{$docTitle}\" ({$docCode}) qui nécessite votre vérification.",
                'action_url' => "/documents/verification?document_id={$event->document->id}",
            ],
            'submitted_for_verification' => [
                'title' => 'Document soumis pour vérification',
                'message' => "{$actorName} a soumis le document \"{$docTitle}\" ({$docCode}) pour vérification.",
                'action_url' => "/documents/verification?document_id={$event->document->id}",
            ],
            'verified' => [
                'title' => 'Document vérifié - Approbation requise',
                'message' => "{$actorName} a vérifié le document \"{$docTitle}\" ({$docCode}). Votre approbation est requise.",
                'action_url' => "/documents/approbation?document_id={$event->document->id}",
            ],
            'approved' => [
                'title' => 'Document approuvé',
                'message' => "{$actorName} a approuvé votre document \"{$docTitle}\" ({$docCode}). Il est maintenant publié.",
                'action_url' => "/documents/{$event->document->id}",
            ],
            'rejected' => [
                'title' => 'Document rejeté',
                'message' => "{$actorName} a rejeté votre document \"{$docTitle}\" ({$docCode}). Raison: {$event->reason}",
                'action_url' => "/documents/{$event->document->id}",
            ],
            'code_released' => [
                'title' => 'Code document disponible',
                'message' => "Le code {$docCode} est maintenant disponible pour réutilisation suite au rejet du document \"{$docTitle}\".",
                'action_url' => '/documents/create',
            ],
            default => [
                'title' => 'Notification document',
                'message' => "Action \"{$event->action}\" sur le document \"{$docTitle}\" ({$docCode}).",
                'action_url' => "/documents/{$event->document->id}",
            ],
        };
    }
}
