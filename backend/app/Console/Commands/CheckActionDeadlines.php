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
        
        // Les rappels métier sont envoyés à J-7, J-3 et J-1.
        $approachingActions = Action::whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $now)
            ->whereDate('deadline', '<=', $now->copy()->addDays(7))
            ->get();

        foreach ($approachingActions as $action) {
            $daysRemaining = $now->diffInDays($action->deadline_date, false);
            
            if (in_array($daysRemaining, [1, 3, 7], true)) {
                event(new ActionDeadlineApproaching($action, (int)$daysRemaining));
                $this->line("⚠️  Deadline approaching: {$action->title} ({$daysRemaining} days)");
            }
        }

        // Actions en retard
        $overdueActions = Action::whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $now)
            ->get();

        foreach ($overdueActions as $action) {
            $daysOverdue = abs($now->diffInDays($action->deadline, false));
            
            event(new ActionOverdue($action, (int)$daysOverdue));
            $this->line("🔴 Overdue: {$action->title} ({$daysOverdue} days late)");
        }

        $this->info("✅ Checked {$approachingActions->count()} approaching and {$overdueActions->count()} overdue actions");

        return Command::SUCCESS;
    }
}
