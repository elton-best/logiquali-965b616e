<?php

namespace App\Jobs;

use App\Services\TrialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTrialExpirations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TrialService $trialService): void
    {
        Log::info('[TrialExpiration] Starting trial expiration process');

        $expiredCount = $trialService->expireTrials();

        Log::info("[TrialExpiration] Expired {$expiredCount} trial(s)");
    }
}
