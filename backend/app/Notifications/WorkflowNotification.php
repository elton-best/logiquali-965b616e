<?php

namespace App\Notifications;

use App\Models\ValidationWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly ValidationWorkflow $workflow,
        private readonly string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'workflow_validation',
            'event' => $this->event,
            'message' => $this->message,
            'validation_id' => $this->workflow->id,
            'objet_type' => $this->workflow->objet_type,
            'objet_id' => $this->workflow->objet_id,
            'status' => $this->workflow->status,
            'url' => '/company/validations/' . $this->workflow->id,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable)->data;
    }
}
