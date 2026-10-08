<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoArchiveDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $documentsToArchive = Document::published()
            ->whereNotNull('review_due_date')
            ->where('review_due_date', '<', now())
            ->get();

        $count = 0;
        foreach ($documentsToArchive as $document) {
            $document->update([
                'status' => 'obsolete',
                'archived_at' => now(),
                'is_active' => false,
            ]);
            $count++;
        }

        Log::info("Auto-archivage: {$count} documents archivés");
    }
}
