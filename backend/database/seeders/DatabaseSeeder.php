<?php

namespace Database\Seeders;

use App\Services\RolePermissionBaselineService;
use App\Services\DocumentTypeConfigurationBootstrapService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ====================================
            // DONNÉES DE RÉFÉRENCE
            // ====================================
            ResponsibilitiesSeeder::class,
            AxeSeeder::class,
            WorkflowStateSeeder::class,
            DocumentCategorySeeder::class,
            DocumentWorkflowSeeder::class,
            TransferReasonCodeSeeder::class,

            // ====================================
            // NORMES ET MODULES
            // ====================================
            NormSeeder::class,
            ModuleSeeder::class,
            CommonQHSECatalogSeeder::class,
            AllISOStandardsSeeder::class,
            LinkCommonModulesToNormsSeeder::class,
            NormativeCatalogMatrixSeeder::class,

            // ====================================
            // PERMISSIONS ET RÔLES UNIFIÉS
            // ====================================
            PermissionCatalogSeeder::class,   // RBAC-1 — Catalogue unifié ~255 permissions
            UnifiedPermissionsSeeder::class,
            SuperAdminSeeder::class,
            DocumentWorkflowPermissionsSeeder::class,

            // ====================================
            // CONFIGURATION NOMENCLATURE
            // ====================================
            ProcessCatalogSeeder::class,
        ]);

        // Bootstrap documentaire centralisé (scope enterprise, idempotent)
        app(DocumentTypeConfigurationBootstrapService::class)->initializeForAllEnterprises();

        // Durcissement permanent: réaligne systématiquement toutes les baselines rôles
        // après seeding, puis purge les permissions directes des admin_entreprise.
        app(RolePermissionBaselineService::class)->syncAll(true);
    }
}
