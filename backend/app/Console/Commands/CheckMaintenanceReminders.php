<?php

namespace App\Console\Commands;

use App\Models\Maintenance;
use App\Models\User;
use App\Notifications\MaintenanceReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckMaintenanceReminders extends Command
{
    protected $signature = 'maintenances:check-reminders';
    protected $description = 'Vérifier et envoyer les rappels de maintenances';

    public function handle()
    {
        $today = Carbon::today();
        $alertDays = [30, 15, 7, 3, 1];

        $remindersSent = 0;

        foreach ($alertDays as $days) {
            $targetDate = $today->copy()->addDays($days);

            $maintenances = Maintenance::where('statut', 'planifie')
                ->whereDate('date_prevue', $targetDate)
                ->with(['equipement'])
                ->get();

            foreach ($maintenances as $maintenance) {
                $equipement = $maintenance->equipement;
                if (!$equipement) {
                    continue;
                }

                $users = User::query()
                    ->where('enterprise_id', $equipement->enterprise_id)
                    ->where('site_id', $equipement->site_id)
                    ->where('is_active', true)
                    ->permission('maintenances.read')
                    ->get();

                foreach ($users as $user) {
                    $user->notify(new MaintenanceReminderNotification($maintenance, $days));
                    $remindersSent++;
                }

                $equipementLabel = $equipement->nom_commun ?? $equipement->code_complet ?? ('#' . $equipement->id);
                $this->info("Rappel envoyé pour maintenance '{$equipementLabel}' (J-{$days})");
            }
        }

        $this->info("Total rappels maintenances envoyés: {$remindersSent}");
        return 0;
    }
}
