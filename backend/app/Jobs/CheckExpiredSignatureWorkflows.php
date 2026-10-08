<?php

namespace App\Jobs;

use App\Services\SignatureWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiredSignatureWorkflows implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SignatureWorkflowService $service): void
    {
        $expiredWorkflows = $service->checkExpired();

        // Log pour monitoring
        if ($expiredWorkflows->isNotEmpty()) {
            \Log::info("Expired {$expiredWorkflows->count()} signature workflows", [
                'workflow_ids' => $expiredWorkflows->pluck('id')->toArray(),
            ]);
        }
    }
}
