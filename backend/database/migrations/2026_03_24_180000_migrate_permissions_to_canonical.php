<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapping = [
            'equipement.read' => 'equipements.read',
            'equipement.create' => 'equipements.create',
            'equipement.update' => 'equipements.update',
            'equipement.delete' => 'equipements.delete',
            'equipement.manage' => 'equipements.manage',
            'habilitation.read' => 'habilitations.read',
            'habilitation.create' => 'habilitations.create',
            'habilitation.update' => 'habilitations.update',
            'habilitation.delete' => 'habilitations.delete',
            'habilitation.manage' => 'habilitations.manage',
            'indicators.read' => 'indicateurs.read',
            'indicators.create' => 'indicateurs.create',
            'indicators.update' => 'indicateurs.update',
            'indicators.delete' => 'indicateurs.delete',
            'process.read' => 'processes.read',
            'process.create' => 'processes.create',
            'process.update' => 'processes.update',
            'process.delete' => 'processes.delete',
            'process.validate' => 'processes.manage',
            'processes.validate' => 'processes.manage',
            'actions.validate' => 'actions.manage',
            'audits.validate' => 'audits.manage',
            'reclamations.approve' => 'reclamations.manage',
        ];

        $moduleActions = ['read', 'create', 'update', 'delete', 'manage', 'validate'];
        $itemActions = ['read', 'create', 'update', 'delete', 'manage'];

        $modules = DB::table('modules')->select('code')->get();
        foreach ($modules as $module) {
            foreach ($moduleActions as $action) {
                $legacy = "{$module->code}.{$action}";
                $canonical = "{$module->code}." . ($action === 'validate' ? 'manage' : $action);
                $mapping[$legacy] = $canonical;
            }
        }

        $subModules = DB::table('sub_modules')
            ->join('modules', 'modules.id', '=', 'sub_modules.module_id')
            ->select('sub_modules.code', 'modules.code as module_code')
            ->get();
        foreach ($subModules as $subModule) {
            foreach ($itemActions as $action) {
                $legacy = "{$subModule->code}.{$action}";
                $canonical = "{$subModule->module_code}.{$subModule->code}.{$action}";
                $mapping[$legacy] = $canonical;
            }
        }

        $sections = DB::table('sub_module_sections')
            ->join('sub_modules', 'sub_modules.id', '=', 'sub_module_sections.sub_module_id')
            ->join('modules', 'modules.id', '=', 'sub_modules.module_id')
            ->select(
                'sub_module_sections.code as section_code',
                'sub_modules.code as sub_module_code',
                'modules.code as module_code'
            )
            ->get();
        foreach ($sections as $section) {
            foreach ($itemActions as $action) {
                $legacy = "{$section->section_code}.{$action}";
                $canonical = "{$section->module_code}.{$section->sub_module_code}.{$section->section_code}.{$action}";
                $mapping[$legacy] = $canonical;
            }
        }

        foreach ($mapping as $legacy => $canonical) {
            if ($legacy === $canonical) {
                continue;
            }

            $legacyPerm = DB::table('permissions')->where('name', $legacy)->first();
            if (!$legacyPerm) {
                continue;
            }

            $canonicalPerm = DB::table('permissions')->where('name', $canonical)->first();
            if ($canonicalPerm) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $legacyPerm->id)
                    ->update(['permission_id' => $canonicalPerm->id]);

                DB::table('model_has_permissions')
                    ->where('permission_id', $legacyPerm->id)
                    ->update(['permission_id' => $canonicalPerm->id]);

                DB::table('permissions')->where('id', $legacyPerm->id)->delete();
                continue;
            }

            DB::table('permissions')
                ->where('id', $legacyPerm->id)
                ->update(['name' => $canonical]);
        }
    }

    public function down(): void
    {
        // No rollback for canonical migration.
    }
};
