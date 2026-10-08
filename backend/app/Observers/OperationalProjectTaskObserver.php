<?php

namespace App\Observers;

use App\Models\OperationalProjectTask;
use App\Traits\NotifiesSiteUsers;

class OperationalProjectTaskObserver
{
    use NotifiesSiteUsers;

    public function created(OperationalProjectTask $task): void
    {
        $responsibleId = (int) ($task->responsible_user_id ?? $task->assigned_to ?? 0);
        if ($responsibleId > 0) {
            $this->notifyUser($responsibleId, 'operational_task_assigned', [
                'type' => 'operational_task_assigned',
                'message' => "Tâche projet assignée: {$task->title}",
                'urgency' => 'high',
                'action_url' => '/company/operations/operational-planning',
            ], $task->created_by);
        }
    }

    public function updated(OperationalProjectTask $task): void
    {
        if ($task->wasChanged('responsible_user_id') || $task->wasChanged('assigned_to')) {
            $oldResponsibleId = (int) ($task->getOriginal('responsible_user_id') ?? $task->getOriginal('assigned_to') ?? 0);
            if ($oldResponsibleId > 0) {
                $this->notifyUser($oldResponsibleId, 'operational_task_unassigned', [
                    'type' => 'operational_task_unassigned',
                    'message' => "Vous n'êtes plus responsable de la tâche: {$task->title}",
                    'urgency' => 'info',
                    'action_url' => '/company/operations/operational-planning',
                ]);
            }

            $newResponsibleId = (int) ($task->responsible_user_id ?? $task->assigned_to ?? 0);
            if ($newResponsibleId > 0) {
                $this->notifyUser($newResponsibleId, 'operational_task_assigned', [
                    'type' => 'operational_task_assigned',
                    'message' => "Une tâche projet vous a été assignée: {$task->title}",
                    'urgency' => 'high',
                    'action_url' => '/company/operations/operational-planning',
                ], $task->updated_by);
            }
        }
    }
}

