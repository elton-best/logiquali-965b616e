<?php

namespace App\Notifications;

use App\Models\QhsePolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PolicyCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public QhsePolicy $policy)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Nouvelle Politique QHSE',
            'message' => 'Une nouvelle politique QHSE version '.$this->policy->version.' a été créée',
            'policy_id' => $this->policy->id,
            'type' => 'policy_created'
        ];
    }
}
