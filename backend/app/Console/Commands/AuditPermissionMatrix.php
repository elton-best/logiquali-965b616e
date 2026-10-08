<?php

namespace App\Console\Commands;

use App\Models\PermissionNormMapping;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class AuditPermissionMatrix extends Command
{
    protected $signature = 'permissions:audit-matrix
        {--fail-on-critical : Retourne un code erreur si des permissions critiques non mappées sont détectées}
        {--only= : Liste CSV de permissions à auditer (ex: contexte.manage,users.read)}';

    protected $description = 'Audite la cohérence de la matrice permission↔norme↔module(/sous-module/section).';

    public function handle(): int
    {
        $systemPrefixes = User::getSystemPermissionPrefixes()->all();

        $allPermissionsQuery = Permission::query()
            ->where('guard_name', 'web')
            ->select(['id', 'name']);

        $only = trim((string) $this->option('only'));
        if ($only !== '') {
            $names = collect(explode(',', $only))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values();

            if ($names->isEmpty()) {
                $this->warn('Option --only fournie mais aucune permission valide détectée.');
                return self::FAILURE;
            }

            $allPermissionsQuery->whereIn('name', $names->all());
            $this->line('scope=custom');
            $this->line('scope_size=' . $names->count());
        } else {
            $this->line('scope=global');
        }

        $allPermissions = $allPermissionsQuery->get();

        $mappedIds = PermissionNormMapping::query()
            ->pluck('permission_id')
            ->unique()
            ->values()
            ->all();

        $unmapped = $allPermissions
            ->filter(fn ($permission) => !in_array((int) $permission->id, $mappedIds, true))
            ->values();

        $criticalUnmapped = $unmapped->filter(function ($permission) use ($systemPrefixes) {
            $prefix = mb_strtolower(trim(explode('.', (string) $permission->name)[0] ?? ''));
            return $prefix !== '' && !in_array($prefix, $systemPrefixes, true);
        })->values();

        $invalidMappingsCount = DB::table('permission_norm_mappings as pnm')
            ->leftJoin('permissions as p', 'p.id', '=', 'pnm.permission_id')
            ->leftJoin('norms as n', 'n.id', '=', 'pnm.norm_id')
            ->leftJoin('modules as m', 'm.id', '=', 'pnm.module_id')
            ->whereNull('p.id')
            ->orWhereNull('n.id')
            ->orWhereNull('m.id')
            ->count();

        $this->info('Audit matrice permissions');
        $this->line('permissions_total=' . $allPermissions->count());
        $this->line('permissions_mapped=' . count($mappedIds));
        $this->line('permissions_unmapped=' . $unmapped->count());
        $this->line('critical_unmapped=' . $criticalUnmapped->count());
        $this->line('invalid_mappings=' . $invalidMappingsCount);

        if ($criticalUnmapped->isNotEmpty()) {
            $this->warn('critical_unmapped_names=' . $criticalUnmapped->pluck('name')->take(20)->implode(','));
        }

        if ($invalidMappingsCount > 0) {
            $this->warn('Des mappings invalides référencent des entités supprimées/inexistantes.');
        }

        if ((bool) $this->option('fail-on-critical') && ($criticalUnmapped->isNotEmpty() || $invalidMappingsCount > 0)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
