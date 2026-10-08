<?php

namespace App\Jobs;

use App\Models\EnterpriseCertification;
use App\Notifications\CertificationExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiredCertifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Certifications expirant dans 90 jours
        $expiringSoon = EnterpriseCertification::expiringSoon(90)->get();

        foreach ($expiringSoon as $certification) {
            // Mettre à jour le statut
            $certification->updateStatus();

            // Notifier les admins de l'entreprise
            $admins = $certification->enterprise->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin_entreprise', 'super_admin']);
                })
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new CertificationExpiringNotification($certification));
            }
        }

        // Marquer les certifications expirées
        $expired = EnterpriseCertification::expired()
            ->where('status', '!=', 'expired')
            ->get();

        foreach ($expired as $certification) {
            $certification->update(['status' => 'expired']);
        }
    }
}
