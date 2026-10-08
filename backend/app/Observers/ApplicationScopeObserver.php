<?php

namespace App\Observers;

use App\Traits\NotifiesSiteUsers;

class ApplicationScopeObserver
{
    use NotifiesSiteUsers;

    public function created($scope): void
    {
        if ($scope->site_id) {
            $this->notifySiteUsers($scope->site_id, 'application_scope_created', [
                'type' => 'application_scope_created',
                'message' => "Nouveau domaine d'application défini",
                'urgency' => 'info',
                'action_url' => "/company/iso/context/application-scope",
            ]);
        }
    }

    public function updated($scope): void
    {
        if ($scope->site_id) {
            $this->notifySiteUsers($scope->site_id, 'application_scope_updated', [
                'type' => 'application_scope_updated',
                'message' => "Domaine d'application mis à jour",
                'urgency' => 'info',
                'action_url' => "/company/iso/context/application-scope",
            ]);
        }
    }
}
