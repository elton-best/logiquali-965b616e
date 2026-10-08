<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\User;
use App\Notifications\AuditReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAuditReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Audits planifiés dans 30 jours (1 mois)
        $this->sendRemindersForPeriod(30, 'one_month');

        // Audits planifiés dans 7 jours (1 semaine)
        $this->sendRemindersForPeriod(7, 'one_week');

        // Audits planifiés demain
        $this->sendRemindersForPeriod(1, 'one_day');

        // Audits en retard
        $this->sendOverdueReminders();
    }

    /**
     * Envoyer les rappels pour une période donnée
     */
    private function sendRemindersForPeriod(int $daysBeforeAudit, string $reminderType): void
    {
        $targetDate = now()->addDays($daysBeforeAudit)->toDateString();

        $audits = Audit::where('status', 'planned')
            ->where('reminder_enabled', true)
            ->whereDate('planned_date', $targetDate)
            ->where(function ($query) use ($daysBeforeAudit) {
                $query->whereNull('reminder_sent_at')
                    ->orWhere('reminder_days_before', '>=', $daysBeforeAudit);
            })
            ->with(['leadAuditor', 'site', 'enterprise'])
            ->get();

        foreach ($audits as $audit) {
            $this->sendReminderForAudit($audit, $reminderType);
        }

        Log::info("Audit reminders sent for period: {$reminderType}", [
            'count' => $audits->count(),
            'target_date' => $targetDate,
        ]);
    }

    /**
     * Envoyer les rappels pour les audits en retard
     */
    private function sendOverdueReminders(): void
    {
        $audits = Audit::where('status', 'planned')
            ->where('reminder_enabled', true)
            ->whereDate('planned_date', '<', now()->toDateString())
            ->with(['leadAuditor', 'site', 'enterprise'])
            ->get();

        foreach ($audits as $audit) {
            $this->sendReminderForAudit($audit, 'overdue');
        }

        Log::info("Overdue audit reminders sent", [
            'count' => $audits->count(),
        ]);
    }

    /**
     * Envoyer un rappel pour un audit spécifique
     */
    private function sendReminderForAudit(Audit $audit, string $reminderType): void
    {
        try {
            // Récupérer les destinataires
            $recipients = $this->getRecipientsForAudit($audit);

            foreach ($recipients as $recipient) {
                // Vérifier si un rappel de ce type a déjà été envoyé
                $existingReminder = DB::table('audit_reminders')
                    ->where('audit_id', $audit->id)
                    ->where('user_id', $recipient->id)
                    ->where('type', $reminderType)
                    ->where('status', 'sent')
                    ->exists();

                if (!$existingReminder) {
                    // Créer l'entrée de rappel
                    $reminderId = DB::table('audit_reminders')->insertGetId([
                        'audit_id' => $audit->id,
                        'user_id' => $recipient->id,
                        'type' => $reminderType,
                        'scheduled_at' => now(),
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    try {
                        // Envoyer la notification
                        $recipient->notify(new AuditReminderNotification($audit, $reminderType));

                        // Mettre à jour le statut
                        DB::table('audit_reminders')
                            ->where('id', $reminderId)
                            ->update([
                                'status' => 'sent',
                                'sent_at' => now(),
                                'updated_at' => now(),
                            ]);
                    } catch (\Exception $e) {
                        DB::table('audit_reminders')
                            ->where('id', $reminderId)
                            ->update([
                                'status' => 'failed',
                                'error_message' => $e->getMessage(),
                                'updated_at' => now(),
                            ]);

                        Log::error("Failed to send audit reminder", [
                            'audit_id' => $audit->id,
                            'user_id' => $recipient->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Mettre à jour le timestamp du dernier rappel envoyé
            if ($reminderType === 'one_month') {
                $audit->update(['reminder_sent_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error("Error processing audit reminder", [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Récupérer les destinataires pour un audit
     */
    private function getRecipientsForAudit(Audit $audit): array
    {
        $recipients = [];

        // Auditeur principal
        if ($audit->leadAuditor) {
            $recipients[] = $audit->leadAuditor;
        }

        // Membres de l'équipe d'audit (si stockés comme IDs)
        if ($audit->team_members && is_array($audit->team_members)) {
            $teamMembers = User::whereIn('id', $audit->team_members)->get();
            foreach ($teamMembers as $member) {
                $recipients[] = $member;
            }
        }

        // Audités via la relation
        $auditees = $audit->auditees()->get();
        foreach ($auditees as $auditee) {
            $recipients[] = $auditee;
        }

        // Dédupliquer par ID
        return collect($recipients)
            ->unique('id')
            ->filter()
            ->values()
            ->all();
    }
}
