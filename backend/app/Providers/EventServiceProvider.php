<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // User Events
        \App\Events\User\UserCreated::class => [
            \App\Listeners\User\SendWelcomeNotification::class,
        ],
        
        \App\Events\User\PasswordResetRequested::class => [
            \App\Listeners\User\SendPasswordResetNotification::class,
        ],
        
        // Document Events
        \App\Events\Document\DocumentSubmittedForApproval::class => [
            \App\Listeners\Document\SendDocumentWorkflowNotification::class,
        ],
        
        \App\Events\Document\DocumentPublished::class => [
            \App\Listeners\Document\SendDocumentWorkflowNotification::class,
        ],
        
        \App\Events\DocumentWorkflowEvent::class => [
            \App\Listeners\SendDocumentWorkflowNotification::class,
        ],
        
        // Complaint Events
        \App\Events\Complaint\ComplaintSubmitted::class => [
            \App\Listeners\Complaint\SendComplaintNotifications::class,
        ],
        
        \App\Events\Complaint\ComplaintAssigned::class => [
            \App\Listeners\Complaint\SendComplaintNotifications::class,
        ],
        
        \App\Events\Complaint\ComplaintReplied::class => [
            \App\Listeners\Complaint\SendComplaintNotifications::class,
        ],
        
        \App\Events\Complaint\ComplaintClosed::class => [
            \App\Listeners\Complaint\SendComplaintNotifications::class,
        ],
        
        // Enterprise Events
        \App\Events\Enterprise\EnterpriseRegistered::class => [
            \App\Listeners\Enterprise\SendEnterpriseNotifications::class,
        ],
        
        \App\Events\Enterprise\EnterpriseApproved::class => [
            \App\Listeners\Enterprise\SendEnterpriseDecisionNotification::class,
        ],
        
        \App\Events\Enterprise\EnterpriseRejected::class => [
            \App\Listeners\Enterprise\SendEnterpriseDecisionNotification::class,
        ],
        
        // Action Events
        \App\Events\Action\ActionAssigned::class => [
            \App\Listeners\Action\SendActionNotifications::class,
        ],
        
        \App\Events\Action\ActionDeadlineApproaching::class => [
            \App\Listeners\Action\SendActionNotifications::class,
        ],
        
        \App\Events\Action\ActionOverdue::class => [
            \App\Listeners\Action\SendActionNotifications::class,
        ],
        
        // Subscription Events
        \App\Events\Subscription\SubscriptionActivated::class => [
            \App\Listeners\Subscription\SendSubscriptionNotifications::class,
        ],
        
        \App\Events\Subscription\SubscriptionExpiring::class => [
            \App\Listeners\Subscription\SendSubscriptionNotifications::class,
        ],
        
        \App\Events\Subscription\SubscriptionExpired::class => [
            \App\Listeners\Subscription\SendSubscriptionNotifications::class,
        ],
        
        // Comment Events
        \App\Events\Comment\UserMentioned::class => [
            \App\Listeners\Comment\SendMentionNotification::class,
        ],
        
        // System Events - Export
        \App\Events\System\ExportCompleted::class => [
            \App\Listeners\System\SendDataProcessingNotification::class,
        ],
        
        // System Events - Import
        \App\Events\System\ImportCompleted::class => [
            \App\Listeners\System\SendDataProcessingNotification::class,
        ],
        
        // Subscription Update Events
        \App\Events\SubscriptionUpdated::class => [
            \App\Listeners\SyncCollaboratorPermissions::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
