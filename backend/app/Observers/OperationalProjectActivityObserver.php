<?php

namespace App\Observers;

use App\Models\OperationalProjectActivity;
use App\Traits\NotifiesSiteUsers;

class OperationalProjectActivityObserver
{
    use NotifiesSiteUsers;

    public function created(OperationalProjectActivity $activity): void
    {
        if ($activity->responsible_user_id) {
            $this->notifyUser($activity->responsible_user_id, 'operational_activity_assigned', [
                'type' => 'operational_activity_assigned',
                'message' => "Activité projet assignée: {$activity->title}",
                'urgency' => 'high',
                'action_url' => '/company/operations/operational-planning',
            ], $activity->created_by);
        }
    }

    public function updated(OperationalProjectActivity $activity): void
    {
        if ($activity->wasChanged('responsible_user_id')) {
            $oldResponsibleId = (int) ($activity->getOriginal('responsible_user_id') ?? 0);
            if ($oldResponsibleId > 0) {
                $this->notifyUser($oldResponsibleId, 'operational_activity_unassigned', [
                    'type' => 'operational_activity_unassigned',
                    'message' => "Vous n'êtes plus responsable de l'activité: {$activity->title}",
                    'urgency' => 'info',
                    'action_url' => '/company/operations/operational-planning',
                ]);
            }

            if ($activity->responsible_user_id) {
                $this->notifyUser((int) $activity->responsible_user_id, 'operational_activity_assigned', [
                    'type' => 'operational_activity_assigned',
                    'message' => "Une activité projet vous a été assignée: {$activity->title}",
                    'urgency' => 'high',
                    'action_url' => '/company/operations/operational-planning',
                ], $activity->updated_by);
            }
        }
    }
}

