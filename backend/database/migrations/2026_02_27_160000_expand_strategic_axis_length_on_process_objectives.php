<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('process_objectives') || !Schema::hasColumn('process_objectives', 'strategic_axis')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE process_objectives ALTER COLUMN strategic_axis TYPE VARCHAR(255)');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE process_objectives MODIFY strategic_axis VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('process_objectives') || !Schema::hasColumn('process_objectives', 'strategic_axis')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE process_objectives ALTER COLUMN strategic_axis TYPE VARCHAR(20)');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE process_objectives MODIFY strategic_axis VARCHAR(20) NULL');
        }
    }
};

