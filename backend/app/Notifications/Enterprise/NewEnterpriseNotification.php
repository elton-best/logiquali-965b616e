<?php

namespace App\Notifications\Enterprise;

use App\Models\Enterprise;
use App\Models\User;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnterpriseNotification extends Notification
{
    use Queueable, LogsNotifications;

    protected Enterprise $enterprise;
    protected User $admin;

    /**
     * Create a new notification instance.
     */
    public function __construct(Enterprise $enterprise, User $admin)
    {
        $this->enterprise = $enterprise;
        $this->admin = $admin;
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
            ->subject("[BestQHSE Admin] Nouvelle demande d'inscription entreprise")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle entreprise vient de s'inscrire sur la plateforme.")
            ->line("")
            ->line("**Informations de l'entreprise :**")
            ->line("- Nom : {$this->enterprise->name}")
            ->line("- Adresse : {$this->enterprise->address}")
            ->line("- Administrateur : {$this->admin->name} ({$this->admin->email})")
            ->line("- Date d'inscription : " . $this->enterprise->created_at->format('d/m/Y H:i'))
            ->line("")
            ->action('Examiner la demande', url("/admin/enterprises/{$this->enterprise->id}"))
            ->line("Merci de traiter cette demande dans les meilleurs délais.")
            ->salutation("Cordialement,\nSystème BestQHSE");
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_enterprise_registration',
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'admin_email' => $this->admin->email,
            'action_url' => url("/admin/enterprises/{$this->enterprise->id}"),
            'message' => "Nouvelle inscription : {$this->enterprise->name}",
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'new_enterprise_registration',
            'title' => 'Nouvelle inscription entreprise',
            'message' => "L'entreprise {$this->enterprise->name} demande son inscription",
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'admin_name' => $this->admin->name,
            'admin_email' => $this->admin->email,
            'action_url' => url("/admin/enterprises/{$this->enterprise->id}"),
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
