<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentRevisionDueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckDocumentRevisions extends Command
{
    protected $signature = 'documents:check-revisions';
    protected $description = 'Check for documents due for revision and send notifications';

    public function handle()
    {
        $this->info('Checking document revisions...');

        // Get documents due for revision in the next 30 days
        $documentsToReview = Document::whereNotNull('review_due_date')
            ->where('review_due_date', '<=', now()->addDays(30))
            ->where('workflow_status', '!=', 'obsolete')
            ->with(['creator', 'site'])
            ->get();

        $notificationsSent = 0;

        foreach ($documentsToReview as $document) {
            $daysUntilDue = now()->diffInDays($document->review_due_date, false);
            
            // Skip if already overdue by more than 7 days
            if ($daysUntilDue < -7) {
                continue;
            }

            // Send notifications at specific intervals: 30, 15, 7, 3, 1 days before and on due date
            $notificationDays = [30, 15, 7, 3, 1, 0];
            
            if (in_array($daysUntilDue, $notificationDays)) {
                // Find responsible users (creator + site managers + enterprise admins)
                $usersToNotify = collect();
                
                // Add document creator
                if ($document->creator) {
                    $usersToNotify->push($document->creator);
                }
                
                // Add site managers and enterprise admins
                $managers = User::where(function($query) use ($document) {
                    $query->where('site_id', $document->site_id)
                          ->whereHas('roles', function($q) {
                              $q->whereIn('name', ['site_manager', 'admin_entreprise']);
                          });
                })->orWhere(function($query) use ($document) {
                    $query->where('enterprise_id', $document->enterprise_id)
                          ->whereHas('roles', function($q) {
                              $q->where('name', 'admin_entreprise');
                          });
                })->get();
                
                $usersToNotify = $usersToNotify->merge($managers)->unique('id');

                // Send notifications
                Notification::send(
                    $usersToNotify,
                    new DocumentRevisionDueNotification($document, $daysUntilDue)
                );

                $notificationsSent += $usersToNotify->count();
                
                $this->line("Sent notifications for document {$document->code} ({$daysUntilDue} days) to {$usersToNotify->count()} users");
            }
        }

        $this->info("Document revision check completed. {$notificationsSent} notifications sent for {$documentsToReview->count()} documents.");
        
        return 0;
    }
}
