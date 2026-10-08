<?php

namespace App\Events;

use App\Models\Document;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentWorkflowEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public string $action,
        public ?User $actor = null,
        public ?string $reason = null
    ) {}

    public function getNotificationData(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'document_title' => $this->document->title ?? $this->document->titre,
            'action' => $this->action,
            'actor_name' => $this->actor?->name,
            'reason' => $this->reason,
        ];
    }
}
