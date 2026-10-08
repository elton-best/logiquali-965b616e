<?php

namespace App\Observers;

use App\Traits\NotifiesSiteUsers;

class OpportunityObserver
{
    use NotifiesSiteUsers;

    public function created($opportunity): void
    {
        if ($opportunity->site_id) {
            $this->notifySiteUsers($opportunity->site_id, 'opportunity_created', [
                'type' => 'opportunity_created',
                'message' => "Nouvelle opportunité: {$opportunity->title}",
                'urgency' => 'info',
                'action_url' => "/improvement/opportunities/{$opportunity->id}",
            ]);
        }
    }

    public function updated($opportunity): void
    {
        if ($opportunity->wasChanged('status') && $opportunity->site_id) {
            $this->notifySiteUsers($opportunity->site_id, 'opportunity_updated', [
                'type' => 'opportunity_updated',
                'message' => "Opportunité {$opportunity->title} - Statut: {$opportunity->status}",
                'urgency' => 'info',
                'action_url' => "/improvement/opportunities/{$opportunity->id}",
            ]);
        }
    }

    public function deleted($opportunity): void
    {
        if ($opportunity->site_id) {
            $this->notifySiteUsers($opportunity->site_id, 'opportunity_deleted', [
                'type' => 'opportunity_deleted',
                'message' => "Opportunité {$opportunity->title} supprimée",
                'urgency' => 'info',
            ]);
        }
    }
}
