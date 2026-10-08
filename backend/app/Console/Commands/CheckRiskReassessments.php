<?php

namespace App\Console\Commands;

use App\Models\Risk;
use App\Models\User;
use App\Notifications\RiskReassessmentNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckRiskReassessments extends Command
{
    protected $signature = 'risks:check-reassessments';
    protected $description = 'Vérifier les réévaluations de risques périodiques';

    public function handle()
    {
        $today = Carbon::today();
        $remindersSent = 0;

        // Risques sans évaluation depuis 12 mois ou plus
        $risks = Risk::where(function($query) use ($today) {
            $query->whereNull('last_assessment_date')
                  ->orWhere('last_assessment_date', '<=', $today->copy()->subMonths(12));
        })->get();

        foreach ($risks as $risk) {
            $monthsOverdue = $risk->last_assessment_date 
                ? $today->diffInMonths($risk->last_assessment_date)
                : 12;

            $users = User::where('enterprise_id', $risk->enterprise_id)
                ->where(function ($query) {
                    $query->whereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['admin_entreprise', 'site_manager']);
                    });
                })
                ->get();

            foreach ($users as $user) {
                $user->notify(new RiskReassessmentNotification($risk, $monthsOverdue));
                $remindersSent++;
            }

            $this->info("Rappel envoyé pour risque '{$risk->title}' ({$monthsOverdue} mois)");
        }

        $this->info("Total rappels réévaluations envoyés: {$remindersSent}");
        return 0;
    }
}
