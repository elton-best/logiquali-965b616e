<?php

namespace App\Observers;

use App\Traits\NotifiesSiteUsers;

class StrategicAxisObserver
{
    use NotifiesSiteUsers;

    public function created($axis): void
    {
        if ($axis->site_id) {
            $this->notifySiteUsers($axis->site_id, 'strategic_axis_created', [
                'type' => 'strategic_axis_created',
                'message' => "Nouvel axe stratégique: {$axis->name}",
                'urgency' => 'info',
                'action_url' => "/company/strategic-axes/{$axis->id}",
            ]);
        }
    }

    public function deleted($axis): void
    {
        if ($axis->site_id) {
            $this->notifySiteUsers($axis->site_id, 'strategic_axis_deleted', [
                'type' => 'strategic_axis_deleted',
                'message' => "Axe stratégique {$axis->name} supprimé",
                'urgency' => 'info',
            ]);
        }
    }
}
