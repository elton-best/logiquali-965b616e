<?php

namespace App\Notifications\Document;

use App\Models\Document;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentWorkflowNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $workflowType;
    protected $document;
    protected $actor;
    protected array $context;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $workflowType, $document, $actor = null, array $context = [])
    {
        // approval_request | verification_request | rejection_decision_required | publication
        // verification_completed | approval_completed
        $this->workflowType = $workflowType;
        $this->document = $document;
        $this->actor = $actor;
        $this->context = $context;
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

        return match ($this->workflowType) {
            'approval_request' => $this->approvalRequestMail($notifiable),
            'verification_request' => $this->verificationRequestMail($notifiable),
            'rejection_decision_required' => $this->rejectionDecisionMail($notifiable),
            'verification_completed' => $this->verificationCompletedMail($notifiable),
            'approval_completed' => $this->approvalCompletedMail($notifiable),
            default => $this->publicationMail($notifiable),
        };
    }

    /**
     * Mail pour demande d'approbation
     */
    protected function approvalRequestMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un collaborateur';
        $documentUrl = $this->buildActionUrl("/documents/{$this->document->id}?action=approve");
        $userName = $notifiable->name ?? 'Utilisateur';

        return (new MailMessage)
            ->subject($this->formatSubject("Demande d'approbation : {$this->document->title}"))
            ->view('emails.document.workflows.approval-request', [
                'userName' => $userName,
                'actorName' => $actorName,
                'documentTitle' => $this->document->title,
                'documentCode' => $this->document->code,
                'documentVersion' => $this->document->version,
                'documentType' => $this->document->type ?? 'Document',
                'documentUrl' => $documentUrl,
                'documentId' => $this->document->id,
                'createdAt' => $this->document->created_at?->format('d/m/Y H:i') ?? '',
            ]);
    }

    /**
     * Mail pour demande de vérification
     */
    protected function verificationRequestMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un collaborateur';
        $documentUrl = $this->buildActionUrl("/documents/{$this->document->id}?action=verify");
        $userName = $notifiable->name ?? 'Utilisateur';

        return (new MailMessage)
            ->subject($this->formatSubject("⏱️ Vérification requise : {$this->document->title}"))
            ->view('emails.document.workflows.verification-request', [
                'userName' => $userName,
                'actorName' => $actorName,
                'documentTitle' => $this->document->title,
                'documentCode' => $this->document->code,
                'documentVersion' => $this->document->version,
                'documentUrl' => $documentUrl,
                'documentId' => $this->document->id,
                'createdAt' => $this->document->created_at?->format('d/m/Y H:i') ?? '',
            ]);
    }

    /**
     * Mail pour décision de rejet par le soumissionnaire
     */
    protected function rejectionDecisionMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un valideur';
        $documentUrl = $this->buildActionUrl("/documents/{$this->document->id}?action=handle_rejection");
        $rejectionReason = $this->context['rejection_reason'] ?? $this->document->rejection_reason ?? 'Non précisé';
        $userName = $notifiable->name ?? 'Utilisateur';

        return (new MailMessage)
            ->subject($this->formatSubject("❌ Décision requise : Rejet de {$this->document->title}"))
            ->view('emails.document.workflows.rejection-decision', [
                'userName' => $userName,
                'actorName' => $actorName,
                'documentTitle' => $this->document->title,
                'documentCode' => $this->document->code,
                'documentVersion' => $this->document->version,
                'rejectionReason' => $rejectionReason,
                'documentUrl' => $documentUrl,
                'documentId' => $this->document->id,
                'rejectedAt' => $this->context['rejected_at'] ?? now()->format('d/m/Y H:i'),
            ]);
    }

    /**
     * Mail pour publication
     */
    protected function publicationMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un administrateur';
        $documentUrl = $this->buildActionUrl("/documents/{$this->document->id}");
        $userName = $notifiable->name ?? 'Utilisateur';

        return (new MailMessage)
            ->subject($this->formatSubject("Nouveau document publié : {$this->document->title}"))
            ->view('emails.document.workflows.publication', [
                'userName' => $userName,
                'actorName' => $actorName,
                'documentTitle' => $this->document->title,
                'documentCode' => $this->document->code,
                'documentVersion' => $this->document->version,
                'documentUrl' => $documentUrl,
                'documentId' => $this->document->id,
                'publishedAt' => now()->format('d/m/Y H:i'),
            ]);
    }

    protected function verificationCompletedMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un collaborateur';
        $documentUrl = $this->buildActionUrl("/documents/{$this->document->id}");

        return (new MailMessage)
            ->subject($this->formatSubject("✓ Vérification complétée : {$this->document->title}"))
            ->view('emails.document.workflows.verification-completed', [
                'userName' => $notifiable->name,
                'verifierName' => $actorName,
                'documentTitle' => $this->document->title,
                'documentCode' => $this->document->code,
                'documentVersion' => $this->document->version,
                'documentType' => $this->document->type ?? 'Document',
                'documentUrl' => $documentUrl,
                'documentId' => $this->document->id,
                'completedAt' => now()->format('d/m/Y H:i'),
            ]);
    }

    protected function approvalCompletedMail(object $notifiable): MailMessage
    {
        $actorName = $this->actor ? $this->actor->name : 'Un collaborateur';
        $outcome = (string) ($this->context['outcome'] ?? 'approved');
        $outcomeLabel = $outcome === 'rejected' ? 'rejete' : 'approuve';
        $userName = $notifiable->name ?? 'Utilisateur';

        return (new MailMessage)
            ->subject($this->formatSubject("Approbation finalisee : {$this->document->title}"))
            ->greeting("Bonjour {$userName},")
            ->line("L'approbation de **{$this->document->title}** a deja ete finalisee par {$actorName}.")
            ->line("Resultat: **{$outcomeLabel}**")
            ->line("**Code :** {$this->document->code}")
            ->action('Voir le document', $this->buildActionUrl("/company/documents/{$this->document->id}"))
            ->salutation($this->formatSalutation());
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $message = match ($this->workflowType) {
            'approval_request' => "Demande d'approbation : {$this->document->title}",
            'verification_request' => "Demande de verification : {$this->document->title}",
            'rejection_decision_required' => "Action requise apres rejet : {$this->document->title}",
            'verification_completed' => "Verification deja finalisee : {$this->document->title}",
            'approval_completed' => "Approbation deja finalisee : {$this->document->title}",
            default => "Document publié : {$this->document->title}",
        };

        return $this->formatBaseArray(
            type: 'document_workflow',
            entityId: $this->document->id,
            ref: $this->document->code,
            url: $this->buildActionUrl("/company/documents/{$this->document->id}"),
            additionalData: [
                'workflow_type' => $this->workflowType,
                'document_title' => $this->document->title,
                'document_version' => $this->document->version,
                'context' => $this->context,
                'message' => $message,
            ]
        );
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $message = match ($this->workflowType) {
            'approval_request' => "Demande d'approbation : {$this->document->title}",
            'verification_request' => "Demande de verification : {$this->document->title}",
            'rejection_decision_required' => "Action requise apres rejet : {$this->document->title}",
            'verification_completed' => "Verification deja finalisee : {$this->document->title}",
            'approval_completed' => "Approbation deja finalisee : {$this->document->title}",
            default => "Document publié : {$this->document->title}",
        };

        return new BroadcastMessage([
            'type' => 'document_workflow',
            'workflow_type' => $this->workflowType,
            'entity_id' => $this->document->id,
            'entity_ref' => $this->document->code,
            'context' => $this->context,
            'message' => $message,
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
