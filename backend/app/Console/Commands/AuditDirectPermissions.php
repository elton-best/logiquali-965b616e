<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditDirectPermissions extends Command
{
    protected $signature = 'permissions:audit-direct {--fail-on-detected : Retourne un code erreur si des permissions directes sont détectées} {--purge : Supprime toutes les permissions directes utilisateur détectées}';

    protected $description = 'Audite (et optionnellement purge) les permissions directes utilisateur pour valider le mode RBAC roles-only.';

    public function handle(): int
    {
        $spatieRows = DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->count();

        $legacyTableExists = Schema::hasTable('user_permissions');
        $legacyRows = $legacyTableExists
            ? DB::table('user_permissions')->count()
            : 0;

        $usersWithDirectFromSpatie = DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->distinct()
            ->pluck('model_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $usersWithDirectFromLegacy = $legacyTableExists
            ? DB::table('user_permissions')
                ->distinct()
                ->pluck('user_id')
                ->map(fn($id) => (int) $id)
                ->all()
            : [];

        $impactedUsers = collect(array_merge($usersWithDirectFromSpatie, $usersWithDirectFromLegacy))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ((bool) $this->option('purge')) {
            DB::transaction(function (): void {
                DB::table('model_has_permissions')
                    ->where('model_type', User::class)
                    ->delete();

                if ($legacyTableExists) {
                    DB::table('user_permissions')->delete();
                }
            });

            $this->warn(
                $legacyTableExists
                    ? 'Purge appliquée: permissions directes supprimées de model_has_permissions et user_permissions.'
                    : 'Purge appliquée: permissions directes supprimées de model_has_permissions.'
            );
            $spatieRows = 0;
            $legacyRows = 0;
        }

        $this->info('Audit permissions directes utilisateur');
        $this->line('model_has_permissions(User)=' . $spatieRows);
        $this->line('user_permissions=' . $legacyRows . ($legacyTableExists ? '' : ' (table supprimée)'));
        $this->line('users_impacted=' . $impactedUsers->count());

        if ($impactedUsers->isNotEmpty()) {
            $this->line('sample_user_ids=' . $impactedUsers->take(20)->implode(','));
        }

        $detected = ($spatieRows + $legacyRows) > 0;
        if ($detected && (bool) $this->option('fail-on-detected')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
