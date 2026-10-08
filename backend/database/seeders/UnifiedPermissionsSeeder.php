<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use App\Models\User;
use App\Services\RolePermissionBaselineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UnifiedPermissionsSeeder extends Seeder
{
    /**
     * Permissions ISO étendues historiquement seedées dans QhsePermissionsSeeder.
     * Elles sont désormais intégrées ici pour conserver une source unique.
     *
     * @return array<int, string>
     */
    private function getIsoExtensionPermissions(): array
    {
        return (array) config('role_baselines.iso_extension_permissions', []);
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('UnifiedPermissionsSeeder interrompu en production (reset RBAC destructif).');
            return;
        }

        DB::transaction(function () {
            $this->command->info('🚀 Création du système de permissions unifié...');

            // Créer les permissions par catégorie
            $this->createBusinessModelPermissions();
            $this->createSystemModelPermissions();
            $this->createIsoExtensionPermissions();
            $this->syncCatalogPermissions();

            // Créer les rôles avec assignations
            $this->createRoles();
            $this->backfillCoreRoleAssignments();

            $this->command->info('✅ Système unifié créé : ' . Permission::count() . ' permissions + ' . Role::count() . ' rôles');
        });
    }

    private function createBusinessModelPermissions(): void
    {
        $businessModels = [
            // === ISO 9001 - QUALITÉ ===
            'actions',
            'activities',
            'audits',
            'audit_programs',
            'non_conformities',
            'risks',
            'opportunities',
            'objectives',
            'processes',
            'process_reviews',
            'documents',
            'stakeholders',
            'contexts',
            'organization_contexts',
            'application_scopes',
            'management_reviews',
            'qhse_policies',
            'job_descriptions',
            'responsibilities',
            'team_members',
            'indicateurs',
            'reclamations',
            'plan_actions',
            'strategic_axes',
            'reports',
            'employee_evaluations',
            'client_satisfaction_forms',
            'satisfaction_surveys',

            // === SUPPORT (Point 7) ===
            'formations',
            'habilitations',
            'communications',
            'equipements',
            'maintenances',
            'codification_elements',
            'document_inventories',
            'calibration_plans',
            'training_plans',

            // === ISO 45001 - SST ===
            'duerps',
            'emergency_procedures',
            'verification_reglementaires',

            // === ISO 14001 - ENVIRONNEMENT ===
            'aspect_environnementaux',
            'obligation_conformite_environnementales',
            'operational_controls',

            // === ISO 50001 - ÉNERGIE ===
            'consommation_energies',
            'ipes',

            // === SYSTÈME ===
            'enterprises',
            'sites',
            'complaints'
        ];

        $actions = ['read', 'create', 'update', 'delete', 'manage'];

        foreach ($businessModels as $model) {
            foreach ($actions as $action) {
                $this->firstOrCreatePermission("{$model}.{$action}");
            }
        }

        $this->command->info("   ✓ permissions métier créées (" . (count($businessModels) * count($actions)) . ")");
    }

    private function createSystemModelPermissions(): void
    {
        $systemPermissions = [
            // Users (7)
            'users.read',
            'users.create',
            'users.update',
            'users.delete',
            'users.manage',
            'users.assign_roles',
            'users.reset_password',
            // Personnel aliases used by policies/routes (4)
            'personnel.read',
            'personnel.create',
            'personnel.update',
            'personnel.delete',

            // Roles (5) 
            'roles.read',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign',

            // Permissions (4)
            'permissions.read',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            // Offers (5)
            'offers.read',
            'offers.create',
            'offers.update',
            'offers.delete',
            'offers.subscribe',

            // Subscriptions (5)
            'subscriptions.read',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.cancel',
            'subscriptions.renew',
            'subscriptions.delete',

            // UI access (read-only)
            'dashboard.read',
            'norm_library.read',
            'settings.read',
            'settings.update',
            'leadership.read',
            'performance.read',
            'competence_matrix.read',
            'equipements.transfer',

            // Document Workflow Permissions
            'verify_documents',
            'approve_documents',

            // Super admin specific
            'norms.read',
            'norms.manage',
            'search.read',
            'sessions.read',
            'sessions.revoke',
            'security_audit.read',
        ];

        foreach ($systemPermissions as $permission) {
            $this->firstOrCreatePermission($permission);
        }

        $this->command->info("   ✓ 33 permissions système créées");
    }

    private function createIsoExtensionPermissions(): void
    {
        foreach ($this->getIsoExtensionPermissions() as $permissionName) {
            $this->firstOrCreatePermission($permissionName);
        }

        $this->command->info('   ✓ ' . count($this->getIsoExtensionPermissions()) . ' permissions ISO étendues créées');
    }

    /**
     * Synchronise les permissions catalogue à partir des modules/sous-modules/sections.
     * Les entrées déjà existantes sont conservées et enrichies sans doublon.
     */
    private function syncCatalogPermissions(): void
    {
        $moduleActions = ['read', 'create', 'update', 'delete', 'manage'];
        $itemActions = ['read', 'create', 'update', 'delete', 'manage'];
        $createdPermissions = collect();

        $modules = Module::query()->select('id', 'code')->get();
        foreach ($modules as $module) {
            foreach ($moduleActions as $action) {
                $createdPermissions->push($this->firstOrCreatePermission("{$module->code}.{$action}"));
            }
        }

        $subModules = SubModule::query()
            ->select('id', 'code', 'module_id')
            ->with(['module:id,code'])
            ->get();
        foreach ($subModules as $subModule) {
            $moduleCode = $subModule->module?->code;
            if (!$moduleCode) {
                continue;
            }
            foreach ($itemActions as $action) {
                $createdPermissions->push(
                    $this->firstOrCreatePermission("{$moduleCode}.{$subModule->code}.{$action}")
                );
            }
        }

        $sections = SubModuleSection::query()
            ->select('id', 'code', 'sub_module_id')
            ->with(['subModule:id,code,module_id', 'subModule.module:id,code'])
            ->get();
        foreach ($sections as $section) {
            $subModule = $section->subModule;
            $moduleCode = $subModule?->module?->code;
            $subModuleCode = $subModule?->code;
            if (!$moduleCode || !$subModuleCode) {
                continue;
            }
            foreach ($itemActions as $action) {
                $createdPermissions->push(
                    $this->firstOrCreatePermission("{$moduleCode}.{$subModuleCode}.{$section->code}.{$action}")
                );
            }
        }

        $permissions = $createdPermissions->filter()->unique('name')->values();
        $catalogPermissionNames = $permissions->pluck('name')->all();
        $catalogReadPermissions = $permissions
            ->filter(fn($permission) => str_ends_with($permission->name, '.read'))
            ->pluck('name')
            ->all();

        $this->grantPermissionsIfRoleExists('admin_entreprise', $catalogPermissionNames);
        $this->grantPermissionsIfRoleExists('site_manager', $catalogPermissionNames);

        foreach (['lecteur'] as $roleName) {
            $this->grantPermissionsIfRoleExists($roleName, $catalogReadPermissions);
        }

        $this->command->info('   ✓ Permissions catalogue synchronisées: ' . count($catalogPermissionNames));
    }

    private function createRoles(): void
    {
        $baselineService = app(RolePermissionBaselineService::class);
        $result = $baselineService->syncAll(false);

        $this->command->info('   ✓ ' . count($result['roles']) . ' rôles créés/synchronisés (source unique role_baselines)');
    }

    private function firstOrCreatePermission(string $name): ?Permission
    {
        if (trim($name) === '') {
            return null;
        }

        $permission = Permission::withoutGlobalScopes()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            if (array_key_exists('deleted_at', $permission->getAttributes()) && $permission->getAttribute('deleted_at') !== null) {
                $permission->forceFill(['deleted_at' => null])->save();
            }

            return $permission;
        }

        return Permission::query()->create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    private function grantPermissionsIfRoleExists(string $roleName, array $permissions): void
    {
        /** @var Role|null $role */
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();
        if (!$role || empty($permissions)) {
            return;
        }

        $role->givePermissionTo($permissions);
    }

    private function backfillCoreRoleAssignments(): void
    {
        $allowed = ['admin_entreprise', 'site_manager', 'lecteur'];

        $users = User::query()
            ->where('user_type', 'company')
            ->whereIn('role', $allowed)
            ->get();

        $restored = 0;
        foreach ($users as $user) {
            if ($user->roles()->exists()) {
                continue;
            }

            $fallbackRole = (string) $user->role;
            if (!in_array($fallbackRole, $allowed, true)) {
                continue;
            }

            $user->syncRoles([$fallbackRole]);
            $restored++;
        }

        $superAdmins = User::query()
            ->where('user_type', User::TYPE_SUPER_ADMIN)
            ->get();

        foreach ($superAdmins as $superAdmin) {
            if ($superAdmin->roles()->exists()) {
                continue;
            }
            $superAdmin->syncRoles(['super_admin']);
            $restored++;
        }

        $this->command->info("   ✓ Attributions de rôles restaurées: {$restored}");
    }
}
