<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentApprovalFinalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Régénère le PDF d'un document obsolète avec le filigrane EXPIRÉ.
 * Dispatché depuis DocumentWorkflowController::approve() pour chaque ancienne version.
 */
class GenerateObsoleteWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private readonly int $documentId) {}

    public function handle(DocumentApprovalFinalizer $finalizer): void
    {
        $document = Document::find($this->documentId);
        if (!$document || $document->status !== 'obsolete') {
            return;
        }

        try {
            $finalizer->finalizeObsolete($document);
        } catch (\Throwable $e) {
            Log::warning('GenerateObsoleteWatermarkJob: failed', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
