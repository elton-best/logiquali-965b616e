<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportJobDescriptionCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly object $importLog
    ) {
        //
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
        $duration = $this->importLog->started_at && $this->importLog->completed_at
            ? $this->importLog->started_at->diffForHumans($this->importLog->completed_at, true)
            : 'N/A';

        $message = (new MailMessage)
            ->subject('Import de fiches de poste terminé')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('✅ Votre import de fiches de poste est terminé !')
            ->line('')
            ->line('**Résultats :**')
            ->line("• {$this->importLog->successful_rows} fiches créées avec succès")
            ->line("• {$this->importLog->failed_rows} lignes ignorées (erreurs)")
            ->line("• Temps d'exécution : {$duration}");

        if ($this->importLog->failed_rows > 0 && $this->importLog->error_report_path) {
            $message->action(
                'Télécharger le rapport d\'erreurs',
                url("/api/v1/job-descriptions/import/logs/{$this->importLog->id}/errors")
            );
        }

        $message->line('');
        $message->action('Voir les fiches de poste', url('/company/leadership/fiche_poste'));

        return $message;
    }

    /**
     * Get the array representation of the notification (for database/in-app).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'import_completed',
            'title' => 'Import de fiches de poste terminé',
            'message' => "{$this->importLog->successful_rows} fiches créées, {$this->importLog->failed_rows} erreurs",
            'data' => [
                'import_log_id' => $this->importLog->id,
                'successful_rows' => $this->importLog->successful_rows,
                'failed_rows' => $this->importLog->failed_rows,
                'has_errors' => $this->importLog->failed_rows > 0,
                'error_report_path' => $this->importLog->error_report_path,
                'action_url' => '/company/leadership/fiche_poste',
            ],
        ];
    }
}
