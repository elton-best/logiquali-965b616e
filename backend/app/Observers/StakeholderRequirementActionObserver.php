<?php

namespace App\Observers;

use App\Models\StakeholderRequirementAction;
use App\Traits\NotifiesSiteUsers;

class StakeholderRequirementActionObserver
{
    use NotifiesSiteUsers;

    public function created(StakeholderRequirementAction $action): void
    {
        if ($action->responsible_id) {
            $this->notifyUser($action->responsible_id, 'stakeholder_action_created', [
                'type' => 'stakeholder_action_created',
                'message' => "Action parties prenantes assignée: {$action->title}",
                'urgency' => 'high',
                'action_url' => "/company/stakeholder-actions/{$action->id}",
            ]);
        }
    }

    public function updated(StakeholderRequirementAction $action): void
    {
        if ($action->wasChanged('status') && $action->responsible_id) {
            $this->notifyUser($action->responsible_id, 'stakeholder_action_updated', [
                'type' => 'stakeholder_action_updated',
                'message' => "Action parties prenantes {$action->title} - Statut: {$action->status}",
                'urgency' => 'info',
                'action_url' => "/company/stakeholder-actions/{$action->id}",
            ]);
        }

        if ($action->wasChanged('responsible_id')) {
            $oldResponsibleId = $action->getOriginal('responsible_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'stakeholder_action_unassigned', [
                    'type' => 'stakeholder_action_unassigned',
                    'message' => "Vous n'êtes plus responsable de l'action parties prenantes: {$action->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/stakeholder-actions/{$action->id}",
                ]);
            }
            if ($action->responsible_id) {
                $this->notifyUser($action->responsible_id, 'stakeholder_action_assigned', [
                    'type' => 'stakeholder_action_assigned',
                    'message' => "Une action parties prenantes {$action->title} vous a été assignée",
                    'urgency' => 'high',
                    'action_url' => "/company/stakeholder-actions/{$action->id}",
                ]);
            }
        }
    }
}
