<?php

namespace App\Observers;

use App\Traits\NotifiesSiteUsers;

class ObjectiveObserver
{
    use NotifiesSiteUsers;

    public function created($objective): void
    {
        if ($objective->responsible_id) {
            $this->notifyUser($objective->responsible_id, 'objective_created', [
                'type' => 'objective_created',
                'message' => "Nouvel objectif assigné: {$objective->title}",
                'urgency' => 'info',
                'action_url' => "/company/objectives/{$objective->id}",
            ], $objective->created_by);
        }
    }

    public function updated($objective): void
    {
        if ($objective->wasChanged('status') && $objective->responsible_id) {
            $this->notifyUser($objective->responsible_id, 'objective_updated', [
                'type' => 'objective_updated',
                'message' => "Objectif {$objective->title} - Statut: {$objective->status}",
                'urgency' => 'info',
                'action_url' => "/company/objectives/{$objective->id}",
            ]);
        }

        // Notifier si le responsable change
        if ($objective->wasChanged('responsible_id')) {
            $oldResponsibleId = $objective->getOriginal('responsible_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'objective_unassigned', [
                    'type' => 'objective_unassigned',
                    'message' => "Vous n'êtes plus responsable de l'objectif: {$objective->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/objectives/{$objective->id}",
                ]);
            }
            if ($objective->responsible_id) {
                $this->notifyUser($objective->responsible_id, 'objective_assigned', [
                    'type' => 'objective_assigned',
                    'message' => "Un objectif {$objective->title} vous a été assigné",
                    'urgency' => 'high',
                    'action_url' => "/company/objectives/{$objective->id}",
                ]);
            }
        }
    }

    public function deleted($objective): void
    {
        if ($objective->responsible_id) {
            $this->notifyUser($objective->responsible_id, 'objective_deleted', [
                'type' => 'objective_deleted',
                'message' => "Objectif {$objective->title} supprimé",
                'urgency' => 'info',
            ]);
        }
    }
}
