<?php

namespace App\Console\Commands;

use App\Services\RolePermissionBaselineService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SyncRoleBaselines extends Command
{
    protected $signature = 'permissions:sync-role-baselines {--dry-run : Affiche le résultat sans écrire en base} {--no-purge-admin-direct : Ne purge pas les permissions directes des admin_entreprise}';

    protected $description = 'Synchronise toutes les permissions de rôles selon la matrice unifiée (config/role_baselines.php).';

    public function handle(RolePermissionBaselineService $service): int
    {
        if ((bool) $this->option('dry-run')) {
            $this->info('📋 Role baselines (dry-run)');
            foreach ($service->roleNames() as $roleName) {
                $currentCount = Role::query()
                    ->where('name', $roleName)
                    ->withCount('permissions')
                    ->value('permissions_count') ?? 0;
                $targetCount = count($service->baselinePermissionNamesForRole($roleName));
                $this->line("{$roleName}: current={$currentCount} target={$targetCount}");
            }

            return self::SUCCESS;
        }

        $purgeAdminDirect = !((bool) $this->option('no-purge-admin-direct'));
        $result = $service->syncAll($purgeAdminDirect);

        $this->info('✅ Synchronisation des baselines rôles terminée');
        foreach ($result['roles'] as $roleName => $count) {
            $this->line("{$roleName}={$count}");
        }
        $this->line('admin_users_direct_permissions_cleared=' . $result['admin_users_direct_permissions_cleared']);

        return self::SUCCESS;
    }
}

