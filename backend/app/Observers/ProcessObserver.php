<?php

namespace App\Observers;

use App\Models\Process;

class ProcessObserver
{
    /**
     * Handle the Process "created" event.
     */
    public function created(Process $process): void
    {
        // Créer la version initiale 1.0
        $process->versions()->create([
            'version_number' => '1.0',
            'version_date' => now(),
            'author_user_id' => $process->created_by ?? $process->pilot_id ?? auth()->id() ?? 1,
            'status' => 'draft',
            'changes_description' => 'Version initiale',
            'is_current' => true,
        ]);
    }

    /**
     * Handle the Process "updated" event.
     */
    public function updated(Process $process): void
    {
        // Si le processus est modifié (et qu'on a un utilisateur connecté)
        if ($process->wasChanged() && auth()->check()) {
            // Récupérer la version actuelle
            $currentVersion = $process->currentVersion;

            if ($currentVersion) {
                // Incrémenter le numéro de version (1.0 -> 1.1, 1.9 -> 2.0)
                $parts = explode('.', $currentVersion->version_number);
                $major = (int) $parts[0];
                $minor = isset($parts[1]) ? (int) $parts[1] : 0;

                // Déterminer si c'est une modification majeure ou mineure
                $isMajorChange = $process->wasChanged(['finalite', 'type', 'pilot_id']);

                if ($isMajorChange) {
                    $newVersion = ($major + 1) . '.0';
                } else {
                    $newVersion = $major . '.' . ($minor + 1);
                }

                // Désactiver la version actuelle
                $currentVersion->update(['is_current' => false]);

                // Créer la nouvelle version
                $process->versions()->create([
                    'version_number' => $newVersion,
                    'version_date' => now(),
                    'author_user_id' => $process->updated_by ?? $process->pilot_id ?? auth()->id() ?? 1,
                    'status' => 'draft',
                    'changes_description' => 'Modification automatique',
                    'is_current' => true,
                ]);
            }
        }
    }

    /**
     * Handle the Process "deleted" event.
     */
    public function deleted(Process $process): void
    {
        //
    }

    /**
     * Handle the Process "restored" event.
     */
    public function restored(Process $process): void
    {
        //
    }

    /**
     * Handle the Process "force deleted" event.
     */
    public function forceDeleted(Process $process): void
    {
        //
    }
}
