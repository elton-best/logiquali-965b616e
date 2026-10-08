<?php

namespace App\Console\Commands;

use App\Models\Communication;
use App\Models\User;
use App\Notifications\CommunicationReminderNotification;
use App\Services\WorkingDaysService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckCommunicationReminders extends Command
{
    protected $signature = 'communications:check-reminders';
    protected $description = 'Vérifier et envoyer les rappels de communications';

    public function __construct(
        private WorkingDaysService $workingDaysService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::today();
        $alertDays = [30, 15, 7, 3, 1];
        
        $remindersSent = 0;

        foreach ($alertDays as $days) {
            $targetDate = $today->copy()->addDays($days);
            
            $communications = Communication::where('status', 'planifiee')
                ->whereDate('date_debut', $targetDate)
                ->with(['enterprise'])
                ->get();

            foreach ($communications as $communication) {
                // Vérifier si nous sommes dans la fenêtre de rappel
                if (!$this->isReminderWindowActive($communication)) {
                    continue;
                }

                $notifiedIds = [];

                // Notifier organizer_user_id
                if ($communication->organizer_user_id) {
                    $organizer = User::find($communication->organizer_user_id);
                    if ($organizer && $organizer->is_active) {
                        $organizer->notify(new CommunicationReminderNotification($communication, $days));
                        $remindersSent++;
                        $notifiedIds[] = $organizer->id;
                    }
                }

                // Notifier chargé de communication si interne (responsible_user_id)
                if ($communication->responsible_user_id && !in_array($communication->responsible_user_id, $notifiedIds)) {
                    $responsible = User::find($communication->responsible_user_id);
                    if ($responsible && $responsible->is_active) {
                        $responsible->notify(new CommunicationReminderNotification($communication, $days));
                        $remindersSent++;
                        $notifiedIds[] = $responsible->id;
                    }
                }

                // Notifier participants
                if ($communication->participant_user_ids) {
                    $participantIds = array_diff((array) $communication->participant_user_ids, $notifiedIds);
                    if (!empty($participantIds)) {
                        $participants = User::whereIn('id', $participantIds)->where('is_active', true)->get();
                        foreach ($participants as $participant) {
                            $participant->notify(new CommunicationReminderNotification($communication, $days));
                            $remindersSent++;
                        }
                    }
                }

                $this->info("Rappel envoyé pour '{$communication->designation}' (J-{$days})");
            }
        }

        $this->info("Total rappels communications envoyés: {$remindersSent}");
        return 0;
    }

    private function isReminderWindowActive(Communication $communication): bool
    {
        if (!$communication->date_debut) {
            return false;
        }

        $frequency = $communication->frequency ?? 'ponctuelle';
        $enterpriseId = $communication->enterprise_id ?? 1;

        return $this->workingDaysService->isInWindow(
            Carbon::parse($communication->date_debut),
            $frequency,
            Carbon::today(),
            $enterpriseId
        );
    }
}
