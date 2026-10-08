<?php

namespace App\Observers;

use App\Models\ComplianceObligationAction;
use App\Traits\NotifiesSiteUsers;

class ComplianceObligationActionObserver
{
    use NotifiesSiteUsers;

    public function created(ComplianceObligationAction $action): void
    {
        if ($action->responsible_id) {
            $this->notifyUser($action->responsible_id, 'compliance_action_created', [
                'type' => 'compliance_action_created',
                'message' => "Action conformité assignée: {$action->title}",
                'urgency' => 'high',
                'action_url' => "/company/compliance-actions/{$action->id}",
            ]);
        }
    }

    public function updated(ComplianceObligationAction $action): void
    {
        if ($action->wasChanged('status') && $action->responsible_id) {
            $this->notifyUser($action->responsible_id, 'compliance_action_updated', [
                'type' => 'compliance_action_updated',
                'message' => "Action conformité {$action->title} - Statut: {$action->status}",
                'urgency' => 'info',
                'action_url' => "/company/compliance-actions/{$action->id}",
            ]);
        }

        if ($action->wasChanged('responsible_id')) {
            $oldResponsibleId = $action->getOriginal('responsible_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'compliance_action_unassigned', [
                    'type' => 'compliance_action_unassigned',
                    'message' => "Vous n'êtes plus responsable de l'action conformité: {$action->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/compliance-actions/{$action->id}",
                ]);
            }
            if ($action->responsible_id) {
                $this->notifyUser($action->responsible_id, 'compliance_action_assigned', [
                    'type' => 'compliance_action_assigned',
                    'message' => "Une action conformité {$action->title} vous a été assignée",
                    'urgency' => 'high',
                    'action_url' => "/company/compliance-actions/{$action->id}",
                ]);
            }
        }
    }
}
