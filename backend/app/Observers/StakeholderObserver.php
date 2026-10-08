<?php

namespace App\Observers;

use App\Traits\NotifiesSiteUsers;

class StakeholderObserver
{
    use NotifiesSiteUsers;

    public function created($stakeholder): void
    {
        if ($stakeholder->site_id) {
            $this->notifySiteUsers($stakeholder->site_id, 'stakeholder_created', [
                'type' => 'stakeholder_created',
                'message' => "Nouvelle partie intéressée: {$stakeholder->name}",
                'urgency' => 'info',
                'action_url' => "/company/iso/context/stakeholders",
            ]);
        }
    }

    public function deleted($stakeholder): void
    {
        if ($stakeholder->site_id) {
            $this->notifySiteUsers($stakeholder->site_id, 'stakeholder_deleted', [
                'type' => 'stakeholder_deleted',
                'message' => "Partie intéressée {$stakeholder->name} supprimée",
                'urgency' => 'info',
            ]);
        }
    }
}
