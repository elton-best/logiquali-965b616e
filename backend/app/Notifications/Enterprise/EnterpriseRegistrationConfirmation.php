<?php

namespace App\Notifications\Enterprise;

use App\Models\Enterprise;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnterpriseRegistrationConfirmation extends Notification
{
    use Queueable, LogsNotifications;

    protected Enterprise $enterprise;

    /**
     * Create a new notification instance.
     */
    public function __construct(Enterprise $enterprise)
    {
        $this->enterprise = $enterprise;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject("[BestQHSE] Inscription reçue - En attente de validation")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous avons bien reçu votre demande d'inscription pour l'entreprise **{$this->enterprise->name}**.")
            ->line("")
            ->line("**Informations de votre entreprise :**")
            ->line("- Nom : {$this->enterprise->name}")
            ->line("- Adresse : {$this->enterprise->address}")
            ->line("- Statut : En attente de validation")
            ->line("")
            ->line("Votre dossier est actuellement en cours d'examen par notre équipe.")
            ->line("Vous recevrez un email de confirmation dès que votre compte sera activé.")
            ->line("")
            ->line("**Délai de traitement :** généralement sous 24-48 heures ouvrées.")
            ->line("")
            ->line("Si vous avez des questions, n'hésitez pas à nous contacter.")
            ->salutation("Cordialement,\nL'équipe BestQHSE");
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enterprise_registration_confirmation',
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'status' => $this->enterprise->status,
            'message' => "Inscription de l'entreprise {$this->enterprise->name} reçue - En attente de validation",
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'enterprise_registration_confirmation',
            'title' => 'Inscription reçue',
            'message' => "Votre demande d'inscription est en cours d'examen",
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'status' => $this->enterprise->status,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
