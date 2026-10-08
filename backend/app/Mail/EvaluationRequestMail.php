<?php

namespace App\Mail;

use App\Models\EvaluationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluationRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public EvaluationRequest $evaluationRequest;
    public bool $isReminder;

    /**
     * Create a new message instance.
     */
    public function __construct(EvaluationRequest $evaluationRequest, bool $isReminder = false)
    {
        $this->evaluationRequest = $evaluationRequest;
        $this->isReminder = $isReminder;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isReminder 
            ? '[Rappel] ' . $this->evaluationRequest->subject
            : $this->evaluationRequest->subject;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.evaluation-request',
            with: [
                'request' => $this->evaluationRequest,
                'isReminder' => $this->isReminder,
                'publicUrl' => $this->evaluationRequest->getPublicUrl(),
                'expiresAt' => $this->evaluationRequest->expires_at,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
