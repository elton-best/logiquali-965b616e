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
        
        // Actions avec échéance dans 3 jours (et pas encore complétées)
        $approachingActions = Action::where('status', '!=', 'completed')
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', '>=', $now)
            ->whereDate('deadline_date', '<=', $now->copy()->addDays(3))
            ->get();

        foreach ($approachingActions as $action) {
            $daysRemaining = $now->diffInDays($action->deadline_date, false);
            
            if ($daysRemaining >= 0 && $daysRemaining <= 3) {
                event(new ActionDeadlineApproaching($action, (int)$daysRemaining));
                $this->line("⚠️  Deadline approaching: {$action->title} ({$daysRemaining} days)");
            }
        }

        // Actions en retard
        $overdueActions = Action::where('status', '!=', 'completed')
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', '<', $now)
            ->get();

        foreach ($overdueActions as $action) {
            $daysOverdue = abs($now->diffInDays($action->deadline_date, false));
            
            event(new ActionOverdue($action, (int)$daysOverdue));
            $this->line("🔴 Overdue: {$action->title} ({$daysOverdue} days late)");
        }

        $this->info("✅ Checked {$approachingActions->count()} approaching and {$overdueActions->count()} overdue actions");

        return Command::SUCCESS;
    }
}
