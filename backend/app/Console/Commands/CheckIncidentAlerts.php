<?php

namespace App\Console\Commands;

use App\Events\Action\ActionOverdue;
use App\Models\Action;
use App\Models\Reclamation;
use App\Models\User;
use App\Notifications\Complaint\IncidentAlertNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckIncidentAlerts extends Command
{
    protected $signature = 'incidents:check-alerts {--days=1,7,14,30 : Cadence des alertes (jours de retard)}';

    protected $description = 'Envoie les alertes périodiques pour incidents non clôturés et actions incidents échues';

    public function handle(): int
    {
        $cadenceDays = $this->parseCadenceDays((string) $this->option('days'));
        $today = Carbon::today();

        $this->info('Checking incident alerts...');

        $notifiedIncidents = 0;
        $triggeredActionOverdues = 0;

        $closedStatuses = ['closed', 'resolue', 'résolue', 'fermee', 'fermée', 'resolved'];

        $overdueIncidents = Reclamation::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->where(function ($q) use ($closedStatuses): void {
                $q->whereNull('status')->orWhereNotIn('status', $closedStatuses);
            })
            ->get();

        foreach ($overdueIncidents as $incident) {
            if (!$incident->due_date) {
                continue;
            }

            $daysOverdue = abs($today->diffInDays(Carbon::parse($incident->due_date), false));
            if (!$this->shouldNotifyForDays($daysOverdue, $cadenceDays)) {
                continue;
            }

            $overdueActionsCount = (int) Action::query()
                ->where('source_type', 'reclamation')
                ->where('source_id', $incident->id)
                ->whereNotNull('deadline')
                ->whereDate('deadline', '<', $today)
                ->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])
                ->count();

            $recipients = $this->resolveRecipientsForIncident($incident);
            foreach ($recipients as $recipient) {
                $recipient->notify(new IncidentAlertNotification($incident, $daysOverdue, $overdueActionsCount));
            }

            $notifiedIncidents++;
            $this->line("⚠️ Incident overdue: {$incident->ref} ({$daysOverdue} days, {$recipients->count()} recipient(s))");
        }

        $overdueIncidentActions = Action::query()
            ->where('source_type', 'reclamation')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today)
            ->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])
            ->get();

        foreach ($overdueIncidentActions as $action) {
            if (!$action->deadline) {
                continue;
            }

            $daysOverdue = abs($today->diffInDays(Carbon::parse($action->deadline), false));
            if (!$this->shouldNotifyForDays($daysOverdue, $cadenceDays)) {
                continue;
            }

            event(new ActionOverdue($action, $daysOverdue));
            $triggeredActionOverdues++;
            $this->line("🔴 Incident action overdue: {$action->title} ({$daysOverdue} days)");
        }

        $this->info("✅ Incident alerts done. Incidents notified: {$notifiedIncidents}, overdue action events: {$triggeredActionOverdues}.");

        return Command::SUCCESS;
    }

    private function parseCadenceDays(string $raw): array
    {
        $days = collect(explode(',', $raw))
            ->map(fn ($item) => (int) trim($item))
            ->filter(fn (int $day) => $day > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return empty($days) ? [1, 7, 14, 30] : $days;
    }

    private function shouldNotifyForDays(int $daysOverdue, array $cadenceDays): bool
    {
        return in_array($daysOverdue, $cadenceDays, true);
    }

    private function resolveRecipientsForIncident(Reclamation $incident): Collection
    {
        $userIds = collect([
            (int) ($incident->assigned_to ?? 0),
            (int) ($incident->user_id ?? 0),
            (int) ($incident->site?->manager_id ?? 0),
        ])
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds->all())
            ->get();
    }
}

