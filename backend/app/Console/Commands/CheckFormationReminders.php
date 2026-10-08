<?php

namespace App\Console\Commands;

use App\Models\Formation;
use App\Models\FormationAlert;
use App\Models\User;
use App\Notifications\FormationReminderNotification;
use App\Services\WorkingDaysService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckFormationReminders extends Command
{
    protected $signature = 'formations:check-reminders';
    protected $description = 'Vérifier et envoyer les rappels de formations';

    public function __construct(
        private WorkingDaysService $workingDaysService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::today();
        $remindersSent = 0;
        $alerts = FormationAlert::query()
            ->whereDate('date', $today)
            ->where('sent', false)
            ->with(['formation'])
            ->get();

        foreach ($alerts as $alert) {
            $formation = $alert->formation;
            if (!$formation || !in_array($formation->status, ['planifiee', 'replanifiee'], true)) {
                continue;
            }

            // Vérifier si nous sommes dans la fenêtre de rappel
            if (!$this->isReminderWindowActive($formation)) {
                continue;
            }

            $daysRemaining = max(0, (int) Carbon::today()->diffInDays(Carbon::parse($formation->date_debut), false));

            // Notifier organizer_user_id
            if ($formation->organizer_user_id) {
                $organizer = User::find($formation->organizer_user_id);
                if ($organizer && $organizer->is_active) {
                    $organizer->notify(new FormationReminderNotification($formation, $daysRemaining));
                    $remindersSent++;
                }
            }

            // Notifier formateur si interne (formateur_user_id)
            if ($formation->formateur_user_id) {
                $formateur = User::find($formation->formateur_user_id);
                if ($formateur && $formateur->is_active) {
                    $formateur->notify(new FormationReminderNotification($formation, $daysRemaining));
                    $remindersSent++;
                }
            }

            // Notifier participants (target_user_ids)
            if ($formation->target_user_ids) {
                $participantIds = (array) $formation->target_user_ids;
                $participants = User::whereIn('id', $participantIds)->where('is_active', true)->get();
                foreach ($participants as $participant) {
                    $participant->notify(new FormationReminderNotification($formation, $daysRemaining));
                    $remindersSent++;
                }
            }

            // Compatibilité legacy: notifier le créateur de la formation.
            if ($formation->created_by) {
                $creator = User::find($formation->created_by);
                if ($creator && $creator->is_active) {
                    $creator->notify(new FormationReminderNotification($formation, $daysRemaining));
                    $remindersSent++;
                }
            }

            // Compatibilité legacy: notifier les admins entreprise du site.
            $enterpriseAdmins = User::query()
                ->where('enterprise_id', $formation->enterprise_id)
                ->where('is_active', true)
                ->role('admin_entreprise')
                ->get();
            foreach ($enterpriseAdmins as $enterpriseAdmin) {
                $enterpriseAdmin->notify(new FormationReminderNotification($formation, $daysRemaining));
                $remindersSent++;
            }

            $alert->update([
                'sent' => true,
                'sent_at' => now(),
            ]);

            $this->info("Rappel envoyé pour formation '{$formation->designation}' ({$alert->type})");
        }

        $this->info("Total rappels formations envoyés: {$remindersSent}");
        return 0;
    }

    private function isReminderWindowActive(Formation $formation): bool
    {
        // Vérifier si le rappel est dans la fenêtre (basé sur la fréquence)
        if (!$formation->date_debut) {
            return false;
        }

        $frequency = $formation->frequency ?? 'ponctuelle';
        $enterpriseId = $formation->enterprise_id ?? 1;

        return $this->workingDaysService->isInWindow(
            Carbon::parse($formation->date_debut),
            $frequency,
            Carbon::today(),
            $enterpriseId
        );
    }
}
