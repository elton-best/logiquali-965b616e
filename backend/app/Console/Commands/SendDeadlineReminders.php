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
        $actions = Action::where('status', '!=', 'completed')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', now())
            ->whereDate('deadline', '<=', now()->addDays(3))
            ->with('responsible')
            ->get();

        $count = 0;

        foreach ($actions as $action) {
            if (!$action->responsible_id) continue;

            $daysRemaining = now()->diffInDays($action->deadline, false);
            
            // Vérifier si notification déjà envoyée aujourd'hui
            $alreadySent = DatabaseNotification::where('notifiable_id', $action->responsible_id)
                ->where('notifiable_type', User::class)
                ->where('type', SiteEventNotification::class)
                ->whereRaw("data->>'type' = ?", ['action_deadline_reminder'])
                ->whereRaw("data->>'action_id' = ?", [(string) $action->id])
                ->whereDate('created_at', now())
                ->exists();

            if ($alreadySent) continue;

            $action->responsible?->notify(new SiteEventNotification('action_deadline_reminder', [
                    'type' => 'action_deadline_reminder',
                    'message' => "Action '{$action->title}' - Échéance dans {$daysRemaining} jour(s)",
                    'urgency' => $daysRemaining <= 1 ? 'critical' : 'high',
                    'action_url' => "/company/actions",
                    'action_id' => $action->id,
                    'days_remaining' => $daysRemaining,
                ], null));

            $count++;
        }

        $this->info("✅ {$count} rappel(s) de deadline envoyé(s)");

        return Command::SUCCESS;
    }
}
