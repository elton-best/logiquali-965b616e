<?php

namespace App\Observers;

use App\Models\Process;
use App\Models\ProcessCatalog;
use Illuminate\Support\Facades\Log;

/**
 * Synchronise automatiquement Process ↔ ProcessCatalog.
 *
 * Quand un processus est créé/modifié dans la table `processes`,
 * l'entrée correspondante dans `process_catalogs` est créée ou mise à jour,
 * et vice-versa.
 */
class ProcessCatalogSyncObserver
{
    /**
     * Après création d'un Process → créer/mettre à jour ProcessCatalog.
     */
    public function created(Process $process): void
    {
        $this->syncToProcessCatalog($process);
    }

    /**
     * Après mise à jour d'un Process → mettre à jour ProcessCatalog.
     */
    public function updated(Process $process): void
    {
        $this->syncToProcessCatalog($process);
    }

    /**
     * Après suppression d'un Process → désactiver ProcessCatalog (ne pas supprimer
     * pour préserver l'historique des documents liés).
     */
    public function deleted(Process $process): void
    {
        ProcessCatalog::query()
            ->where('enterprise_id', $process->enterprise_id)
            ->where('internal_code', (string) $process->id)
            ->update(['is_active' => false]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function syncToProcessCatalog(Process $process): void
    {
        try {
            $enterpriseId = $process->enterprise_id;
            $siteId       = $process->site_id ?? null;

            if (!$enterpriseId) {
                return;
            }

            // Dériver l'abréviation depuis le code du processus ou son titre
            $abbreviation = $this->deriveAbbreviation($process);

            ProcessCatalog::query()->updateOrCreate(
                [
                    'enterprise_id' => $enterpriseId,
                    'site_id'       => $siteId,
                    'internal_code' => (string) $process->id,
                ],
                [
                    'name'          => $process->title ?? $process->name ?? "Processus #{$process->id}",
                    'abbreviation'  => $abbreviation,
                    'description'   => $process->description ?? null,
                    'is_active'     => !$process->trashed(),
                    'display_order' => $process->order ?? 0,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('ProcessCatalogSyncObserver: sync failed', [
                'process_id' => $process->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function deriveAbbreviation(Process $process): string
    {
        // Utiliser le code existant si disponible
        if (!empty($process->code)) {
            return strtoupper(substr(trim((string) $process->code), 0, 30));
        }

        // Générer depuis le titre : prendre les initiales des mots
        $title = $process->title ?? $process->name ?? '';
        $words = preg_split('/\s+/', trim($title));
        if (count($words) >= 2) {
            $abbr = implode('', array_map(fn ($w) => strtoupper(substr($w, 0, 1)), array_slice($words, 0, 4)));
            return substr($abbr, 0, 10);
        }

        // Fallback : 3 premières lettres du titre
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 6)) ?: 'PROC';
    }
}
