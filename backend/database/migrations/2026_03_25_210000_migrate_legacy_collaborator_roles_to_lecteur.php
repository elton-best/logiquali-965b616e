<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Legacy collaborator roles removed from the unified catalog.
     *
     * @var array<int, string>
     */
    private array $legacyRoles = [
        'quality_manager',
        'hse_manager',
        'environment_manager',
        'process_owner',
        'auditor',
        'team_leader',
        'operator',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $roleTable = DB::table('roles');

            $lecteurId = $roleTable
                ->where('name', 'lecteur')
                ->where('guard_name', 'web')
                ->value('id');

            if (!$lecteurId) {
                $lecteurId = $roleTable->insertGetId([
                    'name' => 'lecteur',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $legacyRoleIds = $roleTable
                ->where('guard_name', 'web')
                ->whereIn('name', $this->legacyRoles)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values();

            if ($legacyRoleIds->isEmpty()) {
                return;
            }

            $legacyAssignments = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('role_id', $legacyRoleIds)
                ->get(['model_id']);

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('role_id', $legacyRoleIds)
                ->delete();

            $existingLecteurAssignments = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('role_id', $lecteurId)
                ->pluck('model_id')
                ->map(fn($id) => (int) $id)
                ->flip();

            $rows = [];
            foreach ($legacyAssignments as $assignment) {
                $modelId = (int) $assignment->model_id;
                if (isset($existingLecteurAssignments[$modelId])) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $lecteurId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $modelId,
                ];
                $existingLecteurAssignments[$modelId] = true;
            }

            if (!empty($rows)) {
                DB::table('model_has_roles')->insert($rows);
            }

            DB::table('role_has_permissions')
                ->whereIn('role_id', $legacyRoleIds)
                ->delete();

            $roleTable->whereIn('id', $legacyRoleIds)->delete();
        });
    }

    public function down(): void
    {
        // Irreversible data migration: legacy role assignments were merged into lecteur.
    }
};
