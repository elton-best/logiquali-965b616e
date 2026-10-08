<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sub_modules')
            ->where('code', 'planification_maitrise_operationnelle')
            ->update([
                'route' => '/company/iso/operations/operational-planning-control',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // no-op: route rollback not needed
    }
};

