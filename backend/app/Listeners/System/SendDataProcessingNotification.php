<?php

namespace App\Listeners\System;

use App\Events\System\ExportCompleted;
use App\Events\System\ImportCompleted;
use App\Notifications\ExportFailedNotification;
use App\Notifications\ExportReadyNotification;
use App\Notifications\System\DataProcessingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDataProcessingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ExportCompleted|ImportCompleted $event): void
    {
        if ($event instanceof ExportCompleted) {
            if ($event->exportType) {
                if ($event->success) {
                    $event->user->notify(new ExportReadyNotification([
                        'file_name' => $event->fileName,
                        'file_url' => $event->fileUrl,
                        'type' => $event->exportType,
                        'count' => $event->recordCount,
                    ]));
                } else {
                    $errorMessage = 'Export failed';
                    if (is_array($event->errors) && count($event->errors) > 0) {
                        $errorMessage = $event->errors[0];
                    }
                    $event->user->notify(new ExportFailedNotification([
                        'type' => $event->exportType,
                        'error' => $errorMessage,
                    ]));
                }
                return;
            }

            $processingType = 'export';
        } else {
            $processingType = 'import';
        }

        $event->user->notify(
            new DataProcessingNotification(
                $processingType,
                $event->fileName,
                $event->fileUrl,
                $event->recordCount,
                $event->success,
                $event->errors
            )
        );
    }
}
