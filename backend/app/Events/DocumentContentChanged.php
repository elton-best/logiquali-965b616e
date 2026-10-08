<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentContentChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $documentId;
    public int $userId;
    public array $delta;
    public int $version;

    /**
     * Create a new event instance.
     */
    public function __construct(int $documentId, int $userId, array $delta, int $version)
    {
        $this->documentId = $documentId;
        $this->userId = $userId;
        $this->delta = $delta;
        $this->version = $version;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('document.' . $this->documentId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'content.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'delta' => $this->delta,
            'version' => $this->version,
            'timestamp' => now()->timestamp,
        ];
    }
}
