<?php

namespace App\Listeners\Action;

use App\Events\Action\ActionAssigned;
use App\Events\Action\ActionDeadlineApproaching;
use App\Events\Action\ActionOverdue;
use App\Notifications\Action\ActionNotification;
use App\Models\User;
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

        $action = $event->action->loadMissing(['responsible', 'initiator', 'process.pilot', 'process.copilot']);

        // Notification au responsable/pilote de l'action.
        if ($action->responsible) {
            $additionalData = match($eventType) {
                'deadline_approaching' => ['days_remaining' => $event->daysRemaining],
                'overdue' => ['days_overdue' => $event->daysOverdue],
                default => [],
            };

            $action->responsible->notify(
                new ActionNotification($action, $eventType, $additionalData)
            );
        }

        // En cas de retard, prévenir le pilote du processus et le RQ.
        if ($eventType === 'overdue') {
            $recipients = collect([
                $action->process?->pilot,
                $action->process?->copilot,
                $action->initiator,
            ])->filter();

            $rqUsers = User::query()
                ->where('enterprise_id', $action->enterprise_id)
                ->where('is_active', true)
                ->with('roles')
                ->get()
                ->filter(function (User $user): bool {
                    $roles = $user->roles->pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();
                    $roles[] = mb_strtolower((string) $user->role);
                    return collect($roles)->intersect(['rq', 'responsable_qualite', 'quality_manager', 'hse_manager'])->isNotEmpty();
                });

            foreach ($recipients->merge($rqUsers)->unique('id') as $recipient) {
                if ((int) $recipient->id === (int) $action->responsible_id) {
                    continue;
                }
                $recipient->notify(
                    new ActionNotification($action, 'overdue_manager', ['days_overdue' => $event->daysOverdue])
                );
            }
        }
    }
}
