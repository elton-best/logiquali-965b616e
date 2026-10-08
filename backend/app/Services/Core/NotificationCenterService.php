<?php

namespace App\Services\Core;

use App\Mail\EvaluationRequestMail;
use App\Models\EvaluationRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationCenterService
{
    /**
     * Send evaluation request email with light anti-duplication.
     */
    public function sendEvaluationRequest(EvaluationRequest $request, bool $isReminder = false): void
    {
        $lockKey = sprintf(
            'notification:eval-request:%d:%s:%s',
            $request->id,
            $isReminder ? 'reminder' : 'send',
            optional($request->updated_at)?->timestamp ?? now()->timestamp
        );

        if (!Cache::add($lockKey, true, now()->addMinutes(10))) {
            return;
        }

        Mail::to($request->recipient_email)->send(new EvaluationRequestMail($request, $isReminder));

        Log::info('Evaluation request notification sent', [
            'evaluation_request_id' => $request->id,
            'recipient_email' => $request->recipient_email,
            'is_reminder' => $isReminder,
        ]);
    }
}
