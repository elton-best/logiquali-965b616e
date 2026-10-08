<?php

namespace App\Observers;

use App\Models\PlanAction;
use App\Traits\NotifiesSiteUsers;

class PlanActionObserver
{
    use NotifiesSiteUsers;

    public function created(PlanAction $planAction): void
    {
        if ($planAction->responsible_id) {
            $this->notifyUser($planAction->responsible_id, 'plan_action_created', [
                'type' => 'plan_action_created',
                'message' => "Plan d'action assigné: {$planAction->title}",
                'urgency' => 'high',
                'action_url' => "/company/plan-actions/{$planAction->id}",
            ]);
        }
    }

    public function updated(PlanAction $planAction): void
    {
        if ($planAction->wasChanged('status') && $planAction->responsible_id) {
            $this->notifyUser($planAction->responsible_id, 'plan_action_updated', [
                'type' => 'plan_action_updated',
                'message' => "Plan d'action {$planAction->title} - Statut: {$planAction->status}",
                'urgency' => 'info',
                'action_url' => "/company/plan-actions/{$planAction->id}",
            ]);
        }

        if ($planAction->wasChanged('responsible_id')) {
            $oldResponsibleId = $planAction->getOriginal('responsible_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'plan_action_unassigned', [
                    'type' => 'plan_action_unassigned',
                    'message' => "Vous n'êtes plus responsable du plan d'action: {$planAction->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/plan-actions/{$planAction->id}",
                ]);
            }
            if ($planAction->responsible_id) {
                $this->notifyUser($planAction->responsible_id, 'plan_action_assigned', [
                    'type' => 'plan_action_assigned',
                    'message' => "Un plan d'action {$planAction->title} vous a été assigné",
                    'urgency' => 'high',
                    'action_url' => "/company/plan-actions/{$planAction->id}",
                ]);
            }
        }
    }
}
