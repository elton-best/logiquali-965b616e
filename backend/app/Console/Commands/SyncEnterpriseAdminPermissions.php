<?php

namespace App\Console\Commands;

use App\Services\EnterpriseAdminPermissionService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SyncEnterpriseAdminPermissions extends Command
{
    protected $signature = 'permissions:sync-enterprise-admin {--dry-run : Affiche le résultat sans écrire en base}';

    protected $description = 'Synchronise le rôle admin_entreprise avec toutes les permissions entreprise et purge les permissions directes des admins entreprise.';

    public function handle(EnterpriseAdminPermissionService $service): int
    {
        if ((bool) $this->option('dry-run')) {
            $roleCount = Role::query()
                ->where('name', 'admin_entreprise')
                ->withCount('permissions')
                ->value('permissions_count') ?? 0;

            $targetCount = count($service->enterprisePermissionNames());
            $this->info("admin_role_permissions_current={$roleCount}");
            $this->info("admin_role_permissions_target={$targetCount}");

            return self::SUCCESS;
        }

        $result = $service->sync(true);

        $this->info('✅ Synchronisation admin_entreprise terminée');
        $this->line("admin_role_permissions={$result['role_permissions']}");
        $this->line("admin_users_direct_permissions_cleared={$result['users_cleaned']}");

        return self::SUCCESS;
    }
}

