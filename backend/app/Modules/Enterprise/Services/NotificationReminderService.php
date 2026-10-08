<?php

namespace App\Modules\Enterprise\Services;

use App\Models\Audit;
use App\Models\EnterpriseSubscription;
use App\Models\User;
use App\Services\NotificationRecipientService;
use App\Notifications\AuditDeadlineReminder;
use App\Notifications\SubscriptionExpiringReminder;
use Carbon\Carbon;

class NotificationReminderService
{
    private array $reminderDays = [30, 15, 7, 3, 1, 0];
    private int $sendDelayMinutes = 15;

    public function __construct(
        private NotificationRecipientService $recipientService
    ) {}

    public function handle(): void
    {
        $this->sendAuditDeadlineReminders();
        $this->sendSubscriptionExpiryReminders();
    }

    public function sendAuditDeadlineReminders(): void
    {
        foreach ($this->reminderDays as $daysRemaining) {
            $targetDate = Carbon::today()->addDays($daysRemaining);

            $audits = Audit::query()
                ->whereDate('planned_date', $targetDate)
                ->whereNull('actual_date')
                ->get();

            foreach ($audits as $audit) {
                if (!$audit->site_id) {
                    continue;
                }

                $site = $audit->site;
                if (!$site) {
                    continue;
                }

                $recipients = $this->recipientService->getSiteRecipients($site);
                foreach ($recipients as $recipient) {
                    if ($this->alreadyNotified($recipient, 'audit_deadline_reminder', [
                        'audit_id' => $audit->id,
                        'days_remaining' => $daysRemaining,
                    ])) {
                        continue;
                    }

                    $notification = (new AuditDeadlineReminder($audit, $daysRemaining))
                        ->delay(now()->addMinutes($this->sendDelayMinutes));

                    $recipient->notify($notification);
                }
            }
        }
    }

    public function sendSubscriptionExpiryReminders(): void
    {
        foreach ($this->reminderDays as $daysRemaining) {
            $targetDate = Carbon::today()->addDays($daysRemaining);

            $subscriptions = EnterpriseSubscription::query()
                ->where('is_active', true)
                ->whereDate('expiration_date', $targetDate)
                ->with('site')
                ->get();

            foreach ($subscriptions as $subscription) {
                $site = $subscription->site;
                if (!$site) {
                    continue;
                }

                $recipients = $this->recipientService->getSiteRecipients($site);
                foreach ($recipients as $recipient) {
                    if ($this->alreadyNotified($recipient, 'subscription_expiring', [
                        'subscription_id' => $subscription->id,
                        'days_remaining' => $daysRemaining,
                    ])) {
                        continue;
                    }

                    $notification = (new SubscriptionExpiringReminder($subscription, $daysRemaining))
                        ->delay(now()->addMinutes($this->sendDelayMinutes));

                    $recipient->notify($notification);
                }
            }
        }
    }

    private function alreadyNotified(User $user, string $type, array $criteria): bool
    {
        $query = $user->notifications()->where('data->type', $type);

        foreach ($criteria as $key => $value) {
            $query->where("data->{$key}", $value);
        }

        return $query->exists();
    }
}
