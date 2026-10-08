<?php

namespace App\Notifications;

use App\Models\DocumentTypeConfiguration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NomenclaturePropagationSuggestionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private DocumentTypeConfiguration $configuration,
        private User $actor,
        private int $targetSiteId
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'document',
            'title' => 'Mise à jour de nomenclature disponible',
            'message' => sprintf(
                'Le site source a mis à jour la nomenclature %s (%s). Voulez-vous l’appliquer à votre site ?',
                $this->configuration->name,
                $this->configuration->abbreviation
            ),
            'url' => '/company/documents/nomenclature/configurations',
            'action_label' => 'Ouvrir la propagation',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'resource_type' => 'document_type_configuration',
            'resource_id' => $this->configuration->id,
            'quick_action' => [
                'type' => 'nomenclature_propagation',
                'configuration_id' => $this->configuration->id,
                'target_site_id' => $this->targetSiteId,
            ],
        ];
    }
}

