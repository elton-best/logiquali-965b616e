<?php

namespace App\Observers;

use App\Models\TaskTracking;
use App\Models\TaskTrackingHistory;

class TaskTrackingObserver
{
    public function created(TaskTracking $tracking): void
    {
        // Créer l'entrée initiale d'historique
        TaskTrackingHistory::create([
            'task_tracking_id' => $tracking->id,
            'status_old' => null,
            'status_new' => $tracking->status,
            'progress_rate_old' => null,
            'progress_rate_new' => $tracking->progress_rate,
            'notes' => $tracking->notes,
            'changed_by' => auth()->id() ?? $tracking->user_id,
            'changed_at' => now(),
        ]);
    }

    public function updated(TaskTracking $tracking): void
    {
        // Créer une entrée d'historique si le statut ou la progression change
        if ($tracking->wasChanged('status') || $tracking->wasChanged('progress_rate')) {
            TaskTrackingHistory::create([
                'task_tracking_id' => $tracking->id,
                'status_old' => $tracking->getOriginal('status'),
                'status_new' => $tracking->status,
                'progress_rate_old' => $tracking->getOriginal('progress_rate'),
                'progress_rate_new' => $tracking->progress_rate,
                'notes' => $tracking->notes,
                'changed_by' => auth()->id() ?? $tracking->user_id,
                'changed_at' => now(),
            ]);
        }
    }
}
