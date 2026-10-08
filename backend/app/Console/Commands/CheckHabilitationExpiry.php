<?php

namespace App\Console\Commands;

use App\Models\Habilitation;
use App\Notifications\HabilitationExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckHabilitationExpiry extends Command
{
    protected $signature = 'habilitations:check-expiry';
    protected $description = 'Check for expiring habilitations and send notifications';

    public function handle(): int
    {
        $this->info('Vérification des habilitations expirant bientôt...');

        $alertDays = [30, 15, 7, 3, 1];
        $totalNotifications = 0;

        foreach ($alertDays as $days) {
            $habilitations = Habilitation::with('user')
                ->where('expiry_date', '=', now()->addDays($days)->toDateString())
                ->where('status', 'active')
                ->get();

            foreach ($habilitations as $habilitation) {
                try {
                    $habilitation->user->notify(
                        new HabilitationExpiringNotification($habilitation, $days)
                    );
                    
                    $totalNotifications++;
                    
                    $this->line("✓ Notification envoyée à {$habilitation->user->name} pour {$habilitation->title} (J-{$days})");
                    
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification habilitation', [
                        'habilitation_id' => $habilitation->id,
                        'user_id' => $habilitation->user_id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $this->error("✗ Erreur pour {$habilitation->user->name}: {$e->getMessage()}");
                }
            }
        }

        // Check expired habilitations and update status
        $expired = Habilitation::where('expiry_date', '<', now()->toDateString())
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        if ($expired > 0) {
            $this->warn("⚠️ {$expired} habilitation(s) marquée(s) comme expirée(s)");
        }

        $this->info("✅ Terminé. {$totalNotifications} notification(s) envoyée(s)");
        
        return Command::SUCCESS;
    }
}