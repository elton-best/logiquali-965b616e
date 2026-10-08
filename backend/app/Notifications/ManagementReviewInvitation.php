<?php

namespace App\Notifications;

use App\Models\ManagementReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagementReviewInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public ManagementReview $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invitation Revue de Direction - {$this->review->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous êtes invité(e) à participer à la Revue de Direction suivante :")
            ->line("**{$this->review->title}**")
            ->line("Date prévue : " . $this->review->scheduled_date->format('d/m/Y'))
            ->action('Voir les détails', url("/management-reviews/{$this->review->id}"))
            ->line("Merci de confirmer votre présence.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'title' => $this->review->title,
            'scheduled_date' => $this->review->scheduled_date,
            'type' => 'management_review_invitation',
        ];
    }
}
