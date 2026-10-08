<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('communications', 'enterprise_id')) {
            Schema::table('communications', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('UPDATE communications c SET enterprise_id = u.enterprise_id FROM users u WHERE c.enterprise_id IS NULL AND c.created_by = u.id');
        } elseif ($driver === 'mysql') {
            DB::statement('UPDATE communications c JOIN users u ON c.created_by = u.id SET c.enterprise_id = u.enterprise_id WHERE c.enterprise_id IS NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('communications', 'enterprise_id')) {
            Schema::table('communications', function (Blueprint $table) {
                $table->dropConstrainedForeignId('enterprise_id');
            });
        }
    }
};
