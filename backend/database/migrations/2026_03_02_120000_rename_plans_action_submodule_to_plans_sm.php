<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sub_modules')
            ->where('code', 'plans_action')
            ->update([
                'name' => 'Plans du SM',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('sub_modules')
            ->where('code', 'plans_action')
            ->update([
                'name' => 'Plans d\'action',
                'updated_at' => now(),
            ]);
    }
};
