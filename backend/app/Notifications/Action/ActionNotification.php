<?php

namespace App\Notifications\Action;

use App\Models\Action;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActionNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected Action $action;
    protected string $eventType;
    protected array $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Action $action, string $eventType, array $additionalData = [])
    {
        $this->action = $action;
        $this->eventType = $eventType; // 'assigned' | 'deadline_approaching' | 'overdue' | 'overdue_manager'
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return match($this->eventType) {
            'assigned' => $this->assignedMail($notifiable),
            'deadline_approaching' => $this->deadlineApproachingMail($notifiable),
            'overdue' => $this->overdueMail($notifiable),
            'overdue_manager' => $this->overdueManagerMail($notifiable),
        };
    }

    /**
     * Mail pour assignation
     */
    protected function assignedMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->formatSubject("Action assignée - {$this->action->title}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une action d'amélioration vous a été assignée.")
            ->line("")
            ->line("**Action :** {$this->action->title}")
            ->line("**Description :** " . substr($this->action->description, 0, 200))
            ->line("**Échéance :** " . $this->action->deadline?->format('d/m/Y'))
            ->line("**Priorité :** " . ucfirst($this->action->priority ?? 'normale'))
            ->line("")
            ->action('Consulter l\'action', $this->buildActionUrl('/company/actions'))
            ->line("Merci de prendre en charge cette action dans les meilleurs délais.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Mail pour échéance proche
     */
    protected function deadlineApproachingMail(object $notifiable): MailMessage
    {
        $daysRemaining = $this->additionalData['days_remaining'] ?? 0;
        
        return (new MailMessage)
            ->subject($this->formatSubject("⚠️ Échéance proche - {$this->action->title}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'échéance d'une de vos actions approche.")
            ->line("")
            ->line("**Action :** {$this->action->title}")
            ->line("**Échéance :** " . $this->action->deadline?->format('d/m/Y'))
            ->line("**Jours restants :** {$daysRemaining}")
            ->line("**Statut actuel :** " . ucfirst($this->action->status))
            ->line("")
            ->action('Mettre à jour l\'action', $this->buildActionUrl('/company/actions'))
            ->line("Merci de finaliser cette action avant l'échéance.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Mail pour retard (pilote)
     */
    protected function overdueMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->additionalData['days_overdue'] ?? 0;
        
        return (new MailMessage)
            ->subject($this->formatSubject("🔴 Action en retard - {$this->action->title}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une de vos actions est en retard.")
            ->line("")
            ->line("**Action :** {$this->action->title}")
            ->line("**Échéance dépassée de :** {$daysOverdue} jour(s)")
            ->line("**Échéance initiale :** " . $this->action->deadline?->format('d/m/Y'))
            ->line("**Statut actuel :** " . ucfirst($this->action->status))
            ->line("")
            ->action('Traiter l\'action', $this->buildActionUrl('/company/actions'))
            ->line("Merci de régulariser cette situation rapidement.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Mail pour retard (manager)
     */
    protected function overdueManagerMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->additionalData['days_overdue'] ?? 0;
        $pilotName = $this->action->pilot?->name ?? 'Non assigné';
        
        return (new MailMessage)
            ->subject($this->formatSubject("🔴 Alerte Manager - Action en retard"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une action sous votre responsabilité est en retard.")
            ->line("")
            ->line("**Action :** {$this->action->title}")
            ->line("**Pilote :** {$pilotName}")
            ->line("**Retard :** {$daysOverdue} jour(s)")
            ->line("**Échéance initiale :** " . $this->action->deadline?->format('d/m/Y'))
            ->line("**Statut :** " . ucfirst($this->action->status))
            ->line("")
            ->action('Voir les détails', $this->buildActionUrl('/company/actions'))
            ->line("Un suivi avec le pilote peut être nécessaire.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $messages = [
            'assigned' => "Action assignée : {$this->action->title}",
            'deadline_approaching' => "Échéance proche : {$this->action->title}",
            'overdue' => "Action en retard : {$this->action->title}",
            'overdue_manager' => "Alerte Manager - Action en retard : {$this->action->title}",
        ];

        return $this->formatBaseArray(
            type: 'action_notification',
            entityId: $this->action->id,
            ref: $this->action->reference ?? "ACT-{$this->action->id}",
            url: $this->buildActionUrl('/company/actions'),
            additionalData: array_merge([
                'event_type' => $this->eventType,
                'action_title' => $this->action->title,
                'action_status' => $this->action->status,
                'deadline_date' => $this->action->deadline?->toISOString(),
                'message' => $messages[$this->eventType] ?? 'Notification action',
            ], $this->additionalData)
        );
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
