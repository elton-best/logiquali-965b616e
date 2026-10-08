<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyId = DB::table('sub_modules')->where('code', 'organigramme')->value('id');

        if (!$legacyId) {
            return;
        }

        DB::table('norm_sub_module')->where('sub_module_id', $legacyId)->delete();

        DB::table('sub_modules')
            ->where('id', $legacyId)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('sub_modules')
            ->where('code', 'organigramme')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
