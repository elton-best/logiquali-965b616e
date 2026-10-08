<?php

namespace App\Notifications;

use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Maintenance $maintenance,
        public int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->getUrgencyLevel();
        $equipementLabel = $this->equipementLabel();

        return (new MailMessage)
            ->subject("Maintenance à échéance - {$equipementLabel} ({$urgency})")
            ->line("Une maintenance est prévue dans {$this->daysRemaining} jour(s).")
            ->line("**Équipement:** {$equipementLabel}")
            ->line("**Type:** {$this->maintenance->type}")
            ->line("**Date prévue:** {$this->maintenance->date_prevue->format('d/m/Y')}")
            ->action('Voir la maintenance', url("/maintenances/{$this->maintenance->id}"))
            ->line($this->daysRemaining <= 3 ? '⚠️ Intervention urgente requise !' : 'Merci de planifier cette maintenance.');
    }

    public function toArray($notifiable): array
    {
        $equipementLabel = $this->equipementLabel();

        return [
            'type' => 'maintenance_reminder',
            'maintenance_id' => $this->maintenance->id,
            'equipement' => $equipementLabel,
            'type_maintenance' => $this->maintenance->type,
            'date_prevue' => $this->maintenance->date_prevue,
            'days_remaining' => $this->daysRemaining,
            'urgency' => $this->getUrgencyLevel(),
            'message' => "Maintenance '{$equipementLabel}' prévue dans {$this->daysRemaining} jour(s)"
        ];
    }

    private function getUrgencyLevel(): string
    {
        return match (true) {
            $this->daysRemaining <= 1 => 'CRITIQUE',
            $this->daysRemaining <= 3 => 'URGENT',
            $this->daysRemaining <= 7 => 'ÉLEVÉ',
            default => 'NORMAL'
        };
    }

    private function equipementLabel(): string
    {
        return (string) (
            $this->maintenance->equipement?->nom_commun
            ?? $this->maintenance->equipement?->code_complet
            ?? ('#' . $this->maintenance->equipement_id)
        );
    }
}
