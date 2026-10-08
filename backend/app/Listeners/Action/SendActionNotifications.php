<?php

namespace App\Listeners\Action;

use App\Events\Action\ActionAssigned;
use App\Events\Action\ActionDeadlineApproaching;
use App\Events\Action\ActionOverdue;
use App\Notifications\Action\ActionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendActionNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ActionAssigned|ActionDeadlineApproaching|ActionOverdue $event): void
    {
        $eventType = match(true) {
            $event instanceof ActionAssigned => 'assigned',
            $event instanceof ActionDeadlineApproaching => 'deadline_approaching',
            $event instanceof ActionOverdue => 'overdue',
        };

        // Notification au pilote de l'action
        if ($event->action->pilot) {
            $additionalData = match($eventType) {
                'deadline_approaching' => ['days_remaining' => $event->daysRemaining],
                'overdue' => ['days_overdue' => $event->daysOverdue],
                default => [],
            };

            $event->action->pilot->notify(
                new ActionNotification($event->action, $eventType, $additionalData)
            );
        }

        // Si action en retard, notifier aussi le manager
        if ($eventType === 'overdue' && $event->action->manager) {
            $event->action->manager->notify(
                new ActionNotification($event->action, 'overdue_manager', ['days_overdue' => $event->daysOverdue])
            );
        }
    }
}
