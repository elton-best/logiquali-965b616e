<?php

namespace App\Notifications\Complaint;

use App\Models\Reclamation;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ComplaintEvolutionNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $evolutionType;
    protected Reclamation $complaint;
    protected string $message;
    protected ?string $surveyUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $evolutionType,
        Reclamation $complaint,
        string $message,
        ?string $surveyUrl = null
    ) {
        $this->evolutionType = $evolutionType; // 'reply' | 'closure'
        $this->complaint = $complaint;
        $this->message = $message;
        $this->surveyUrl = $surveyUrl;
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

        if ($this->evolutionType === 'reply') {
            return $this->replyMail($notifiable);
        }

        return $this->closureMail($notifiable);
    }

    /**
     * Mail pour réponse
     */
    protected function replyMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->formatSubject("Réponse à votre réclamation - Réf: {$this->complaint->ref}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous avons apporté une réponse à votre réclamation.")
            ->line("**Référence :** {$this->complaint->ref}")
            ->line("**Titre :** {$this->complaint->title}")
            ->line("")
            ->line("**Notre réponse :**")
            ->line($this->message)
            ->line("")
            ->action('Consulter la réclamation', $this->buildActionUrl("/complaints/{$this->complaint->id}"))
            ->line("Si vous avez des questions supplémentaires, n'hésitez pas à nous contacter.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Mail pour clôture
     */
    protected function closureMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->formatSubject("Réclamation clôturée - Réf: {$this->complaint->ref}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre réclamation a été traitée et est maintenant clôturée.")
            ->line("**Référence :** {$this->complaint->ref}")
            ->line("**Titre :** {$this->complaint->title}")
            ->line("")
            ->line("**Résolution :**")
            ->line($this->message)
            ->line("")
            ->action('Consulter la réclamation', $this->buildActionUrl("/complaints/{$this->complaint->id}"));

        if ($this->surveyUrl) {
            $mail->line("")
                ->line("**Votre avis nous intéresse !**")
                ->line("Merci de prendre quelques instants pour évaluer notre traitement de votre réclamation.")
                ->action('Répondre au questionnaire de satisfaction', $this->surveyUrl);
        }

        $mail->line("Nous vous remercions de votre confiance.")
            ->salutation($this->formatSalutation());

        return $mail;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $baseData = $this->formatBaseArray(
            type: 'complaint_evolution',
            entityId: $this->complaint->id,
            ref: $this->complaint->ref,
            url: $this->buildActionUrl("/complaints/{$this->complaint->id}"),
            additionalData: [
                'evolution_type' => $this->evolutionType,
                'complaint_title' => $this->complaint->title,
            ]
        );

        if ($this->evolutionType === 'reply') {
            $baseData['message'] = "Réponse à votre réclamation : {$this->complaint->ref}";
        } else {
            $baseData['message'] = "Réclamation clôturée : {$this->complaint->ref}";
            if ($this->surveyUrl) {
                $baseData['survey_url'] = $this->surveyUrl;
                $baseData['has_survey'] = true;
            }
        }

        return $baseData;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'complaint_evolution',
            'evolution_type' => $this->evolutionType,
            'entity_id' => $this->complaint->id,
            'entity_ref' => $this->complaint->ref,
            'message' => $this->evolutionType === 'reply'
                ? "Nouvelle réponse : {$this->complaint->ref}"
                : "Réclamation clôturée : {$this->complaint->ref}",
        ]);
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
