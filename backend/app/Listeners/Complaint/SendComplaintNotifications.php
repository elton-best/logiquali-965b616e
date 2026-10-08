<?php

namespace App\Listeners\Complaint;

use App\Events\Complaint\ComplaintSubmitted;
use App\Events\Complaint\ComplaintAssigned;
use App\Events\Complaint\ComplaintReplied;
use App\Events\Complaint\ComplaintClosed;
use App\Notifications\Complaint\ComplaintConfirmationNotification;
use App\Notifications\Complaint\ComplaintInternalNotification;
use App\Notifications\Complaint\ComplaintEvolutionNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendComplaintNotifications implements ShouldQueue
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
    public function handle(
        ComplaintSubmitted|ComplaintAssigned|ComplaintReplied|ComplaintClosed $event
    ): void {
        match (true) {
            $event instanceof ComplaintSubmitted => $this->handleSubmitted($event),
            $event instanceof ComplaintAssigned => $this->handleAssigned($event),
            $event instanceof ComplaintReplied => $this->handleReplied($event),
            $event instanceof ComplaintClosed => $this->handleClosed($event),
        };
    }

    /**
     * Handle complaint submitted event
     */
    protected function handleSubmitted(ComplaintSubmitted $event): void
    {
        // 1. Notification de confirmation au client
        if ($event->complaint->user) {
            $event->complaint->user->notify(
                new ComplaintConfirmationNotification($event->complaint)
            );
        }

        // 2. Notification interne aux responsables qualité
        $qualityManagers = User::query()
            ->where('enterprise_id', $event->complaint->enterprise_id)
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin_entreprise', 'site_manager']);
            })
            ->get();

        foreach ($qualityManagers as $manager) {
            $manager->notify(
                new ComplaintInternalNotification('created', $event->complaint)
            );
        }
    }

    /**
     * Handle complaint assigned event
     */
    protected function handleAssigned(ComplaintAssigned $event): void
    {
        // Notification au collaborateur assigné
        $event->assignedTo->notify(
            new ComplaintInternalNotification('assigned', $event->complaint, $event->assignedTo)
        );
    }

    /**
     * Handle complaint replied event
     */
    protected function handleReplied(ComplaintReplied $event): void
    {
        // Notification au client
        if ($event->complaint->user) {
            $event->complaint->user->notify(
                new ComplaintEvolutionNotification(
                    'reply',
                    $event->complaint,
                    $event->message
                )
            );
        }
    }

    /**
     * Handle complaint closed event
     */
    protected function handleClosed(ComplaintClosed $event): void
    {
        // Notification au client avec enquête de satisfaction
        if ($event->complaint->user) {
            $event->complaint->user->notify(
                new ComplaintEvolutionNotification(
                    'closure',
                    $event->complaint,
                    $event->complaint->immediate_response ?? 'Votre réclamation a été traitée.',
                    $event->surveyUrl
                )
            );
        }
    }
}
