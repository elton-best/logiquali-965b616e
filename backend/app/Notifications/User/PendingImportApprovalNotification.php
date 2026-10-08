<?php

namespace App\Notifications\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingImportApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $pendingApprovalUsersCount,
        private readonly int $enterpriseId,
        private readonly int $siteId,
        private readonly ?int $requestedByUserId = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requestedBy = $this->requestedByUserId
            ? User::query()->find($this->requestedByUserId)?->name
            : null;

        $message = (new MailMessage)
            ->subject('Validation requise pour des collaborateurs importés')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un import a créé {$this->pendingApprovalUsersCount} collaborateur(s) en attente de validation.")
            ->line("Entreprise #{$this->enterpriseId} - Site #{$this->siteId}");

        if ($requestedBy) {
            $message->line("Import demandé par : {$requestedBy}");
        }

        return $message
            ->action('Consulter les demandes', url('/company/leadership/personnel'))
            ->line('Merci de valider ou ajuster les permissions avant activation des comptes.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'collaborator_import_pending_approval',
            'title' => 'Import en attente de validation',
            'message' => "{$this->pendingApprovalUsersCount} collaborateur(s) importé(s) à valider.",
            'data' => [
                'pending_approval_users' => $this->pendingApprovalUsersCount,
                'enterprise_id' => $this->enterpriseId,
                'site_id' => $this->siteId,
                'requested_by_user_id' => $this->requestedByUserId,
                'action_url' => '/company/leadership/personnel',
            ],
        ];
    }
}

