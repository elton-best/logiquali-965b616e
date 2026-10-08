<?php

namespace App\Notifications;

use App\Models\DocumentImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly DocumentImport $import,
        public readonly array $stats
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
        $imported = $this->stats['imported'] ?? 0;
        $failed = $this->stats['failed'] ?? 0;
        $total = $imported + $failed;

        $message = (new MailMessage)
            ->subject('Import de documents terminé')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('✅ Votre import de documents est terminé !')
            ->line('')
            ->line('**Résultats :**')
            ->line("• {$imported} documents importés avec succès")
            ->line("• {$failed} documents en erreur")
            ->line("• Total traité : {$total} lignes");

        if ($failed > 0 && !empty($this->stats['errors'])) {
            $message->line('')
                ->line('**Erreurs détectées :**');
            
            $errorCount = min(3, count($this->stats['errors']));
            for ($i = 0; $i < $errorCount; $i++) {
                $error = $this->stats['errors'][$i];
                $message->line("• Ligne {$error['line']}: {$error['message']}");
            }
            
            if (count($this->stats['errors']) > 3) {
                $remaining = count($this->stats['errors']) - 3;
                $message->line("• ... et {$remaining} autres erreurs");
            }
        }

        $message->line('')
            ->action('Voir l\'inventaire documentaire', url('/company/support/document-inventory'));

        return $message;
    }

    /**
     * Get the array representation of the notification (for database/in-app).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $imported = $this->stats['imported'] ?? 0;
        $failed = $this->stats['failed'] ?? 0;

        return [
            'type' => 'document_import_completed',
            'title' => 'Import de documents terminé',
            'message' => "{$imported} documents importés, {$failed} erreurs",
            'data' => [
                'import_id' => $this->import->id,
                'imported' => $imported,
                'failed' => $failed,
                'has_errors' => $failed > 0,
                'errors' => $this->stats['errors'] ?? [],
                'action_url' => '/company/support/document-inventory',
            ],
        ];
    }
}
