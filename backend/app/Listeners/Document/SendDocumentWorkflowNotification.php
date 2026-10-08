<?php

namespace App\Listeners\Document;

use App\Events\Document\DocumentSubmittedForApproval;
use App\Events\Document\DocumentPublished;
use App\Notifications\Document\DocumentWorkflowNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDocumentWorkflowNotification implements ShouldQueue
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
    public function handle(DocumentSubmittedForApproval|DocumentPublished $event): void
    {
        if ($event instanceof DocumentSubmittedForApproval) {
            // Notifier tous les approbateurs
            foreach ($event->approvers as $approver) {
                $approver->notify(
                    new DocumentWorkflowNotification(
                        'approval_request',
                        $event->document,
                        $event->submittedBy
                    )
                );
            }
        } else {
            // DocumentPublished - Notifier tous les utilisateurs concernés
            foreach ($event->concernedUsers as $user) {
                $user->notify(
                    new DocumentWorkflowNotification(
                        'publication',
                        $event->document,
                        $event->publishedBy
                    )
                );
            }
        }
    }
}
