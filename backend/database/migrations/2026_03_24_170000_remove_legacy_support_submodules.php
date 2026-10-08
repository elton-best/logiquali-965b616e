<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supportId = DB::table('modules')->where('code', 'support')->value('id');
        if (!$supportId) {
            return;
        }

        $legacyCodes = [
            'habilitations',
            'epi',
            'vgp',
            'aspects_environnementaux',
            'obligations_conformite_env',
            'consommations_energie',
            'ipe',
        ];

        $legacyIds = DB::table('sub_modules')
            ->where('module_id', $supportId)
            ->whereIn('code', $legacyCodes)
            ->pluck('id')
            ->all();

        if (!$legacyIds) {
            return;
        }

        DB::table('sub_module_sections')
            ->whereIn('sub_module_id', $legacyIds)
            ->delete();

        DB::table('norm_sub_module')
            ->whereIn('sub_module_id', $legacyIds)
            ->delete();

        DB::table('sub_modules')
            ->whereIn('id', $legacyIds)
            ->delete();
    }

    public function down(): void
    {
        // No rollback for legacy cleanup.
    }
};
