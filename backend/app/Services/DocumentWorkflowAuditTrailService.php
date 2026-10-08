<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentWorkflowEvent;
use Illuminate\Support\Facades\DB;

class DocumentWorkflowAuditTrailService
{
    public function recordEvent(
        Document $document,
        string $eventType,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId = null,
        ?string $comment = null,
        array $metadata = []
    ): DocumentWorkflowEvent {
        return DB::transaction(function () use (
            $document,
            $eventType,
            $fromStatus,
            $toStatus,
            $actorUserId,
            $comment,
            $metadata
        ) {
            $lastEvent = DocumentWorkflowEvent::query()
                ->where('document_id', $document->id)
                ->orderByDesc('chain_index')
                ->lockForUpdate()
                ->first();

            $chainIndex = $lastEvent ? ((int) $lastEvent->chain_index + 1) : 1;
            $previousHash = $lastEvent?->event_hash;
            // Normalize to UTC second precision to keep deterministic hash material
            // between creation time and later verification reads from DB.
            $occurredAt = now()->utc()->startOfSecond();

            $payload = [
                'document_id' => $document->id,
                'chain_index' => $chainIndex,
                'event_type' => $eventType,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actorUserId,
                'comment' => $comment,
                'metadata' => $this->sortRecursive($metadata),
                'occurred_at' => $occurredAt->toISOString(),
            ];

            $eventHash = hash(
                'sha256',
                (string) ($previousHash ?? 'GENESIS') . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $eventSignature = hash_hmac('sha256', $eventHash, (string) config('app.key'));

            return DocumentWorkflowEvent::query()->create([
                'document_id' => $document->id,
                'actor_user_id' => $actorUserId,
                'chain_index' => $chainIndex,
                'event_type' => $eventType,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comment' => $comment,
                'metadata' => $metadata,
                'previous_event_hash' => $previousHash,
                'event_hash' => $eventHash,
                'event_signature' => $eventSignature,
                'occurred_at' => $occurredAt,
                'sealed_at' => now(),
            ]);
        });
    }

    public function verifyDocumentChain(Document $document): array
    {
        $events = DocumentWorkflowEvent::query()
            ->where('document_id', $document->id)
            ->orderBy('chain_index')
            ->get();

        $previousHash = null;
        $invalid = [];

        foreach ($events as $event) {
            $payload = [
                'document_id' => $event->document_id,
                'chain_index' => (int) $event->chain_index,
                'event_type' => $event->event_type,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'actor_user_id' => $event->actor_user_id,
                'comment' => $event->comment,
                'metadata' => $this->sortRecursive((array) ($event->metadata ?? [])),
                'occurred_at' => $event->occurred_at?->toISOString(),
            ];

            $expectedHash = hash(
                'sha256',
                (string) ($previousHash ?? 'GENESIS') . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $expectedSignature = hash_hmac('sha256', $expectedHash, (string) config('app.key'));

            if ($expectedHash !== $event->event_hash || $expectedSignature !== $event->event_signature) {
                $invalid[] = [
                    'event_id' => $event->id,
                    'chain_index' => $event->chain_index,
                ];
            }

            $previousHash = $event->event_hash;
        }

        return [
            'valid' => empty($invalid),
            'events_count' => $events->count(),
            'invalid_events' => $invalid,
            'last_hash' => $previousHash,
        ];
    }

    private function sortRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortRecursive($value);
            }
        }

        ksort($data);

        return $data;
    }
}
