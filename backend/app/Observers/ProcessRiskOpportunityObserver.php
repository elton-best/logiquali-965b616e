<?php

namespace App\Observers;

use App\Models\ProcessRiskOpportunity;
use App\Traits\NotifiesSiteUsers;

class ProcessRiskOpportunityObserver
{
    use NotifiesSiteUsers;

    public function created(ProcessRiskOpportunity $item): void
    {
        if ($item->responsible_user_id) {
            $type = $item->type === 'risk' ? 'Risque' : 'Opportunité';
            $this->notifyUser($item->responsible_user_id, 'process_risk_opportunity_created', [
                'type' => 'process_risk_opportunity_created',
                'message' => "{$type} processus assigné: {$item->title}",
                'urgency' => $item->type === 'risk' ? 'high' : 'info',
                'action_url' => "/company/process-risks/{$item->id}",
            ]);
        }
    }

    public function updated(ProcessRiskOpportunity $item): void
    {
        if ($item->wasChanged('status') && $item->responsible_user_id) {
            $type = $item->type === 'risk' ? 'Risque' : 'Opportunité';
            $this->notifyUser($item->responsible_user_id, 'process_risk_opportunity_updated', [
                'type' => 'process_risk_opportunity_updated',
                'message' => "{$type} {$item->title} - Statut: {$item->status}",
                'urgency' => 'info',
                'action_url' => "/company/process-risks/{$item->id}",
            ]);
        }

        if ($item->wasChanged('responsible_user_id')) {
            $type = $item->type === 'risk' ? 'Risque' : 'Opportunité';
            $oldResponsibleId = $item->getOriginal('responsible_user_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'process_risk_opportunity_unassigned', [
                    'type' => 'process_risk_opportunity_unassigned',
                    'message' => "Vous n'êtes plus responsable du {$type} processus: {$item->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/process-risks/{$item->id}",
                ]);
            }
            if ($item->responsible_user_id) {
                $this->notifyUser($item->responsible_user_id, 'process_risk_opportunity_assigned', [
                    'type' => 'process_risk_opportunity_assigned',
                    'message' => "Un {$type} processus {$item->title} vous a été assigné",
                    'urgency' => 'high',
                    'action_url' => "/company/process-risks/{$item->id}",
                ]);
            }
        }
    }
}
