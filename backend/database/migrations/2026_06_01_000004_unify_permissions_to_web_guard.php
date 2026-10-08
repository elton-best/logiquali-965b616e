<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Migration d'unification des guards de permissions.
 *
 * Problème : 11 permissions documentaires ont été créées en guard 'sanctum'
 * alors que tout le reste du système (762 permissions, 13 rôles) est en guard 'web'.
 *
 * Solution : Tout unifier en guard 'web' — le guard majoritaire et celui
 * utilisé par RolePermissionBaselineService (source de vérité).
 *
 * Actions :
 * 1. Supprimer les permissions en guard 'sanctum' (doublons de 'web')
 * 2. Supprimer les rôles orphelins en guard 'sanctum' (verifier, approver)
 * 3. Créer/recréer verifier et approver en guard 'web'
 * 4. Assigner les permissions documentaires à admin_entreprise, site_manager
 * 5. Assigner les permissions granulaires d'import
 */
return new class extends Migration
{
    // Permissions documentaires à unifier
    private const DOCUMENT_PERMISSIONS = [
        'verify_documents'       => 'Vérifier les documents soumis',
        'approve_documents'      => 'Approuver les documents vérifiés',
        'configure_nomenclature' => 'Configurer la nomenclature des codes documents',
        'view_nomenclature'      => 'Consulter la configuration de nomenclature',
        'submit_for_verification'=> 'Soumettre un document pour vérification',
        'create_documents'       => 'Créer des documents',
        'edit_documents'         => 'Modifier des documents',
        'delete_documents'       => 'Supprimer des documents',
        'view_documents'         => 'Consulter les documents',
        'import_documents'       => 'Importer des documents existants',
        'export_documents'       => 'Exporter des documents',
    ];

    // Permissions granulaires d'import (créées par DocumentImportGranularPermissionsSeeder)
    private const IMPORT_GRANULAR_PERMISSIONS = [
        'import_documents.own_site'  => 'Importer pour son propre site uniquement',
        'import_documents.all_sites' => 'Importer pour tous les sites de l\'entreprise',
        'import_documents.rollback'  => 'Annuler (rollback) des imports de documents',
        'import_documents.bulk'      => 'Importer en masse (> 100 lignes)',
    ];

    public function up(): void
    {
        // ── Étape 1 : Vider le cache Spatie ──────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Étape 2 : Supprimer les permissions en guard 'sanctum' ───────────
        // Ces permissions sont des doublons — les mêmes existent en guard 'web'
        $sanctumPermIds = Permission::where('guard_name', 'sanctum')->pluck('id');
        if ($sanctumPermIds->isNotEmpty()) {
            // Détacher des rôles/utilisateurs d'abord
            DB::table('role_has_permissions')->whereIn('permission_id', $sanctumPermIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $sanctumPermIds)->delete();
            Permission::where('guard_name', 'sanctum')->delete();
        }

        // ── Étape 3 : Supprimer les rôles orphelins en guard 'sanctum' ───────
        $sanctumRoleIds = DB::table('roles')->where('guard_name', 'sanctum')->pluck('id');
        if ($sanctumRoleIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('role_id', $sanctumRoleIds)->delete();
            DB::table('model_has_roles')->whereIn('role_id', $sanctumRoleIds)->delete();
            DB::table('roles')->where('guard_name', 'sanctum')->delete();
        }

        // ── Étape 4 : S'assurer que toutes les permissions documentaires ──────
        // existent en guard 'web'
        foreach (array_merge(self::DOCUMENT_PERMISSIONS, self::IMPORT_GRANULAR_PERMISSIONS) as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // ── Étape 5 : Assigner les permissions à admin_entreprise ────────────
        $adminRole = Role::where('name', 'admin_entreprise')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $permissionsToAdd = array_merge(
                array_keys(self::DOCUMENT_PERMISSIONS),
                array_keys(self::IMPORT_GRANULAR_PERMISSIONS)
            );
            // Utiliser givePermissionTo pour ne pas écraser les permissions existantes
            foreach ($permissionsToAdd as $permName) {
                $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                if ($perm && !$adminRole->hasPermissionTo($perm)) {
                    $adminRole->permissions()->attach($perm->id);
                }
            }
        }

        // ── Étape 6 : Assigner les permissions à site_manager ────────────────
        $siteManagerRole = Role::where('name', 'site_manager')->where('guard_name', 'web')->first();
        if ($siteManagerRole) {
            $siteManagerPerms = [
                'view_nomenclature',
                'verify_documents',
                'create_documents',
                'edit_documents',
                'view_documents',
                'import_documents',
                'import_documents.own_site',
                'export_documents',
                'submit_for_verification',
            ];
            foreach ($siteManagerPerms as $permName) {
                $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                if ($perm && !$siteManagerRole->hasPermissionTo($perm)) {
                    $siteManagerRole->permissions()->attach($perm->id);
                }
            }
        }

        // ── Étape 7 : Créer les rôles verifier et approver en guard 'web' ────
        $verifierRole = Role::firstOrCreate(
            ['name' => 'verifier', 'guard_name' => 'web'],
            ['description' => 'Vérificateur de documents']
        );
        $verifierPerms = ['verify_documents', 'view_documents', 'view_nomenclature'];
        foreach ($verifierPerms as $permName) {
            $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
            if ($perm && !$verifierRole->hasPermissionTo($perm)) {
                $verifierRole->permissions()->attach($perm->id);
            }
        }

        $approverRole = Role::firstOrCreate(
            ['name' => 'approver', 'guard_name' => 'web'],
            ['description' => 'Approbateur de documents']
        );
        $approverPerms = ['approve_documents', 'view_documents', 'view_nomenclature'];
        foreach ($approverPerms as $permName) {
            $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
            if ($perm && !$approverRole->hasPermissionTo($perm)) {
                $approverRole->permissions()->attach($perm->id);
            }
        }

        // ── Étape 8 : Assigner les permissions à lecteur ─────────────────────
        $lecteurRole = Role::where('name', 'lecteur')->where('guard_name', 'web')->first();
        if ($lecteurRole) {
            $lecteurPerms = ['view_documents', 'view_nomenclature'];
            foreach ($lecteurPerms as $permName) {
                $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                if ($perm && !$lecteurRole->hasPermissionTo($perm)) {
                    $lecteurRole->permissions()->attach($perm->id);
                }
            }
        }

        // ── Étape 9 : Vider le cache Spatie après modifications ───────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Recréer les permissions en guard 'sanctum' (rollback)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::DOCUMENT_PERMISSIONS as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'sanctum'],
                ['description' => $description]
            );
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
