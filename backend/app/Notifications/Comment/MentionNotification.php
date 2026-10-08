<?php

namespace App\Notifications\Comment;

use App\Models\User;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected User $author;
    protected $comment;
    protected $entity;

    public function __construct(User $author, $comment, $entity)
    {
        $this->author = $author;
        $this->comment = $comment;
        $this->entity = $entity;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        $entityType = class_basename($this->entity);
        $entityTitle = $this->entity->title ?? $this->entity->name ?? "#{$this->entity->id}";
        $commentPreview = substr($this->comment->content, 0, 150);
        
        return (new MailMessage)
            ->subject($this->formatSubject("{$this->author->name} vous a mentionné"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**{$this->author->name}** vous a mentionné dans un commentaire.")
            ->line("")
            ->line("**Contexte :** {$entityType} - {$entityTitle}")
            ->line("")
            ->line("**Commentaire :**")
            ->line("\"{$commentPreview}...\"")
            ->line("")
            ->action('Voir le commentaire', $this->buildCommentUrl())
            ->salutation($this->formatSalutation());
    }

    protected function buildCommentUrl(): string
    {
        $entityType = strtolower(class_basename($this->entity));
        $entityId = $this->entity->id;
        $commentId = $this->comment->id;
        
        return $this->buildActionUrl("/{$entityType}s/{$entityId}#comment-{$commentId}");
    }

    public function toArray(object $notifiable): array
    {
        $entityType = class_basename($this->entity);
        
        return $this->formatBaseArray(
            type: 'user_mentioned',
            entityId: $this->entity->id,
            ref: "{$entityType}-{$this->entity->id}",
            url: $this->buildCommentUrl(),
            additionalData: [
                'author_id' => $this->author->id,
                'author_name' => $this->author->name,
                'comment_id' => $this->comment->id,
                'comment_preview' => substr($this->comment->content, 0, 100),
                'entity_type' => $entityType,
                'entity_title' => $this->entity->title ?? $this->entity->name ?? null,
                'message' => "{$this->author->name} vous a mentionné dans un commentaire",
            ]
        );
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
