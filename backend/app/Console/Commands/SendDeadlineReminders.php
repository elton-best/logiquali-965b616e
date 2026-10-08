<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\User;
use App\Notifications\SiteEventNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class SendDeadlineReminders extends Command
{
    protected $signature = 'notifications:deadline-reminders';
    protected $description = 'Envoyer des rappels pour les actions proches de leur deadline';

    public function handle(): int
    {
        $actions = Action::whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', now())
            ->whereDate('deadline', '<=', now()->addDays(7))
            ->with(['responsible', 'process.pilot', 'process.copilot'])
            ->get();

        $count = 0;

        foreach ($actions as $action) {
            if (!$action->responsible_id) continue;

            $daysRemaining = now()->diffInDays($action->deadline, false);
            if (!in_array($daysRemaining, [1, 3, 7], true)) {
                continue;
            }
            
            // Vérifier si notification déjà envoyée aujourd'hui
            $alreadySent = DatabaseNotification::where('notifiable_id', $action->responsible_id)
                ->where('notifiable_type', User::class)
                ->where('type', SiteEventNotification::class)
                ->whereRaw("data->>'type' = ?", ['action_deadline_reminder'])
                ->whereRaw("data->'data'->>'action_id' = ?", [(string) $action->id])
                ->whereRaw("data->'data'->>'days_remaining' = ?", [(string) $daysRemaining])
                ->whereDate('created_at', now())
                ->exists();

            if ($alreadySent) continue;

            $recipients = collect([$action->responsible, $action->process?->pilot, $action->process?->copilot])
                ->filter()
                ->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new SiteEventNotification('action_deadline_reminder', [
                    'type' => 'action_deadline_reminder',
                    'message' => "Action '{$action->title}' - Échéance dans {$daysRemaining} jour(s)",
                    'urgency' => $daysRemaining <= 1 ? 'critical' : 'high',
                    'action_url' => "/company/actions",
                    'action_id' => $action->id,
                    'days_remaining' => $daysRemaining,
                ], null));
            }

            $count++;
        }

        $this->info("✅ {$count} action(s) rappelée(s)");

        return Command::SUCCESS;
    }
}
