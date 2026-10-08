<?php

namespace App\Jobs;

use App\Services\DocumentCodeRecyclingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredCodeReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(DocumentCodeRecyclingService $service): void
    {
        Log::info('[CodePool] Démarrage nettoyage codes expirés');

        $cleaned = $service->cleanupExpiredReservations();

        Log::info("[CodePool] Nettoyage terminé: {$cleaned} codes libérés");
    }
}
