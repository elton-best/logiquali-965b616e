<?php

namespace App\Notifications;

use App\Models\Risk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskReassessmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Risk $risk,
        public int $monthsOverdue
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lastAssessment = $this->risk->last_assessment_date 
            ? $this->risk->last_assessment_date->format('d/m/Y') 
            : 'Jamais';
            
        return (new MailMessage)
            ->subject("Réévaluation de risque requise - {$this->risk->title}")
            ->line("Un risque nécessite une réévaluation périodique.")
            ->line("**Risque:** {$this->risk->title}")
            ->line("**Dernière évaluation:** {$lastAssessment}")
            ->line("**Retard:** {$this->monthsOverdue} mois")
            ->action('Réévaluer le risque', url("/risks/{$this->risk->id}"))
            ->line('Merci de procéder à la réévaluation dans les meilleurs délais.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'risk_reassessment',
            'risk_id' => $this->risk->id,
            'title' => $this->risk->title,
            'months_overdue' => $this->monthsOverdue,
            'last_assessment' => $this->risk->last_assessment_date,
            'message' => "Réévaluation requise pour le risque '{$this->risk->title}'"
        ];
    }
}