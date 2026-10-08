<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Events\Action\ActionDeadlineApproaching;
use App\Events\Action\ActionOverdue;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckActionDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'actions:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check action deadlines and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking action deadlines...');

        $now = Carbon::now();
        
        // Actions avec échéance dans les 7 jours (cadence J-7, J-3, J-1)
        $approachingActions = Action::whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $now)
            ->whereDate('deadline', '<=', $now->copy()->addDays(7))
            ->get();

        foreach ($approachingActions as $action) {
            $deadline = $action->deadline ? Carbon::parse($action->deadline) : null;
            if (!$deadline) continue;
            
            $daysRemaining = (int) $now->diffInDays($deadline, false);
            
            // Alerter aux paliers clés : 7 jours, 3 jours, 1 jour, et jour J
            if (in_array($daysRemaining, [7, 3, 1, 0], true)) {
                event(new ActionDeadlineApproaching($action, $daysRemaining));
                $this->line("⚠️  Deadline approaching: {$action->title} ({$daysRemaining} days)");
            }
        }

        // Actions en retard
        $overdueActions = Action::whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $now)
            ->get();

        foreach ($overdueActions as $action) {
            $deadline = $action->deadline ? Carbon::parse($action->deadline) : null;
            $daysOverdue = $deadline ? abs((int) $now->diffInDays($deadline, false)) : 1;
            
            event(new ActionOverdue($action, $daysOverdue));
            $this->line("🔴 Overdue: {$action->title} ({$daysOverdue} days late)");
        }

        $this->info("✅ Checked {$approachingActions->count()} approaching and {$overdueActions->count()} overdue actions");

        return Command::SUCCESS;
    }
}
