<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Services\RolePermissionBaselineService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $legacyRoles = [
            'quality_manager',
            'hse_manager',
            'environment_manager',
            'process_owner',
            'auditor',
            'team_leader',
            'operator'
        ];

        // 1. Réassigner les utilisateurs ayant des rôles legacy vers 'lecteur'
        $lecteurRole = Role::where('name', 'lecteur')->where('guard_name', 'web')->first();
        if (!$lecteurRole) {
            $lecteurRole = Role::create(['name' => 'lecteur', 'guard_name' => 'web']);
        }

        $legacyRoleModels = Role::whereIn('name', $legacyRoles)->where('guard_name', 'web')->get();
        foreach ($legacyRoleModels as $legacyRole) {
            $userIds = DB::table('model_has_roles')
                ->where('role_id', $legacyRole->id)
                ->where('model_type', User::class)
                ->pluck('model_id');
            
            foreach ($userIds as $userId) {
                // Remove legacy role
                DB::table('model_has_roles')
                    ->where('role_id', $legacyRole->id)
                    ->where('model_id', $userId)
                    ->where('model_type', User::class)
                    ->delete();
                
                // Add lecteur role if they don't have it
                $hasLecteur = DB::table('model_has_roles')
                    ->where('role_id', $lecteurRole->id)
                    ->where('model_id', $userId)
                    ->where('model_type', User::class)
                    ->exists();
                    
                if (!$hasLecteur) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $lecteurRole->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ]);
                }
            }

            // Supprimer le rôle legacy de la base de données
            $legacyRole->delete();
        }

        // 2. Vider les permissions directes de tous les utilisateurs (RBAC pur)
        DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->delete();

        // 3. Recalculer et réassigner les permissions des rôles système selon le nouveau catalogue
        app(RolePermissionBaselineService::class)->syncAll(true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Difficile de revenir en arrière car on a supprimé des données (permissions directes, etc.)
        // La restauration nécessiterait une sauvegarde de la base de données.
    }
};
