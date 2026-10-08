<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration one-time : synchronise tous les processus existants
 * de la table `processes` vers `process_catalogs`.
 *
 * Après cette migration, l'observer ProcessCatalogSyncObserver
 * maintient la synchronisation en temps réel.
 */
return new class extends Migration
{
    public function up(): void
    {
        $processes = DB::table('processes')
            ->whereNull('deleted_at')
            ->get();

        foreach ($processes as $process) {
            $enterpriseId = $process->enterprise_id ?? null;
            if (!$enterpriseId) {
                continue;
            }

            $abbreviation = $this->deriveAbbreviation($process);

            DB::table('process_catalogs')->updateOrInsert(
                [
                    'enterprise_id' => $enterpriseId,
                    'site_id'       => $process->site_id ?? null,
                    'internal_code' => (string) $process->id,
                ],
                [
                    'name'          => $process->title ?? $process->name ?? "Processus #{$process->id}",
                    'abbreviation'  => $abbreviation,
                    'description'   => $process->description ?? null,
                    'is_active'     => true,
                    'display_order' => $process->order ?? 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Supprimer uniquement les entrées créées par cette migration
        // (celles qui ont un internal_code correspondant à un process.id)
        $processIds = DB::table('processes')->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        if (!empty($processIds)) {
            DB::table('process_catalogs')->whereIn('internal_code', $processIds)->delete();
        }
    }

    private function deriveAbbreviation(object $process): string
    {
        if (!empty($process->code)) {
            return strtoupper(substr(trim((string) $process->code), 0, 30));
        }

        $title = $process->title ?? $process->name ?? '';
        $words = preg_split('/\s+/', trim($title));
        if (count($words) >= 2) {
            $abbr = implode('', array_map(fn ($w) => strtoupper(substr($w, 0, 1)), array_slice($words, 0, 4)));
            return substr($abbr, 0, 10);
        }

        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 6)) ?: 'PROC';
    }
};
