<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Audit $audit;
    private string $reminderType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Audit $audit, string $reminderType)
    {
        $this->audit = $audit;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();

        $siteName = $this->audit->site?->name ?? 'Non défini';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line("**Audit :** {$this->audit->title}")
            ->line("**Référence :** {$this->audit->ref}")
            ->line("**Date prévue :** {$this->audit->planned_date->format('d/m/Y')}")
            ->line("**Site :** {$siteName}");

        if ($this->audit->scope) {
            $mail->line("**Périmètre :** {$this->audit->scope}");
        }

        $mail->action('Voir les détails', url("/company/performance/audits/{$this->audit->id}"));

        if ($this->reminderType === 'overdue') {
            $mail->line('⚠️ Cet audit est en retard. Veuillez mettre à jour le statut ou reprogrammer.');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'audit_reminder',
            'reminder_type' => $this->reminderType,
            'audit_id' => $this->audit->id,
            'audit_ref' => $this->audit->ref,
            'audit_title' => $this->audit->title,
            'planned_date' => $this->audit->planned_date->toDateString(),
            'site_name' => $this->audit->site?->name,
            'message' => $this->getMessage(),
        ];
    }

    /**
     * Get email subject based on reminder type
     */
    private function getSubject(): string
    {
        return match ($this->reminderType) {
            'one_month' => "[Rappel] Audit prévu dans 1 mois : {$this->audit->title}",
            'one_week' => "[Rappel] Audit prévu dans 1 semaine : {$this->audit->title}",
            'one_day' => "[Urgent] Audit prévu demain : {$this->audit->title}",
            'overdue' => "[Action requise] Audit en retard : {$this->audit->title}",
            default => "[Rappel] Audit : {$this->audit->title}",
        };
    }

    /**
     * Get greeting based on reminder type
     */
    private function getGreeting(): string
    {
        return match ($this->reminderType) {
            'overdue' => 'Action requise !',
            'one_day' => 'Rappel urgent !',
            default => 'Rappel d\'audit',
        };
    }

    /**
     * Get message based on reminder type
     */
    private function getMessage(): string
    {
        $formattedDate = $this->audit->planned_date->format('d/m/Y');

        return match ($this->reminderType) {
            'one_month' => "Un audit est programmé pour le {$formattedDate}, soit dans environ 1 mois. Pensez à préparer les documents nécessaires.",
            'one_week' => "L'audit prévu le {$formattedDate} aura lieu dans une semaine. Assurez-vous que tout est prêt.",
            'one_day' => "L'audit prévu le {$formattedDate} est programmé pour demain. Dernière vérification avant le jour J !",
            'overdue' => "L'audit prévu le {$formattedDate} n'a pas été réalisé. Veuillez mettre à jour le statut ou reprogrammer.",
            default => "Un rappel concernant l'audit prévu le {$formattedDate}.",
        };
    }
}
