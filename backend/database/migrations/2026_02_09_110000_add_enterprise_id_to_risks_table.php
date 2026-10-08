<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('risks', 'enterprise_id')) {
            Schema::table('risks', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
            });

            // Backfill from sites
            DB::statement('UPDATE risks r SET enterprise_id = s.enterprise_id FROM sites s WHERE r.site_id = s.id');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('risks', 'enterprise_id')) {
            Schema::table('risks', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            });
        }
    }
};
