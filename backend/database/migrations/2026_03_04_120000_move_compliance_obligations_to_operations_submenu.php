<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sub_modules') || !Schema::hasTable('sub_module_sections')) {
            return;
        }

        $exigencesSubModuleId = DB::table('sub_modules')
            ->where('code', 'exigences_produits_services')
            ->value('id');

        if ($exigencesSubModuleId) {
            DB::table('sub_module_sections')->updateOrInsert(
                ['code' => 'obligations_conformite'],
                [
                    'sub_module_id' => $exigencesSubModuleId,
                    'name' => 'Obligations de conformité',
                    'description' => 'Obligations de conformité applicables aux exigences relatives aux produits et services.',
                    'icon' => 'mdi-gavel',
                    'route' => '/company/iso/operations/compliance-obligations',
                    'order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('sub_modules')
            ->where('code', 'obligations_conformite')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('sub_modules') || !Schema::hasTable('sub_module_sections')) {
            return;
        }

        DB::table('sub_module_sections')
            ->where('code', 'obligations_conformite')
            ->delete();

        DB::table('sub_modules')
            ->where('code', 'obligations_conformite')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
