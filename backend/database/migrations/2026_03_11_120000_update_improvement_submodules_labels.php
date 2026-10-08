<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ameliorationModuleIds = DB::table('modules')
            ->where('code', 'amelioration')
            ->pluck('id');

        if ($ameliorationModuleIds->isEmpty()) {
            return;
        }

        DB::table('sub_modules')
            ->whereIn('module_id', $ameliorationModuleIds)
            ->whereIn('code', [
                'non_conformites',
                'nonconformites',
                'non_conformity',
                'non_conformities',
            ])
            ->update([
                'name' => 'Non-conformités & actions correctives',
                'updated_at' => now(),
            ]);

        DB::table('sub_modules')
            ->whereIn('module_id', $ameliorationModuleIds)
            ->whereIn('code', [
                'actions_correctives',
                'action_corrective',
                'corrective_actions',
            ])
            ->update([
                'is_active' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $ameliorationModuleIds = DB::table('modules')
            ->where('code', 'amelioration')
            ->pluck('id');

        if ($ameliorationModuleIds->isEmpty()) {
            return;
        }

        DB::table('sub_modules')
            ->whereIn('module_id', $ameliorationModuleIds)
            ->whereIn('code', [
                'non_conformites',
                'nonconformites',
                'non_conformity',
                'non_conformities',
            ])
            ->update([
                'name' => 'Non-conformités',
                'updated_at' => now(),
            ]);

        DB::table('sub_modules')
            ->whereIn('module_id', $ameliorationModuleIds)
            ->whereIn('code', [
                'actions_correctives',
                'action_corrective',
                'corrective_actions',
            ])
            ->update([
                'is_active' => 1,
                'updated_at' => now(),
            ]);
    }
};
