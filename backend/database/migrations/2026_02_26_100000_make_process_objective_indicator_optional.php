<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('process_objectives')) {
            if (Schema::hasColumn('process_objectives', 'indicator_id')) {
                DB::statement('ALTER TABLE process_objectives ALTER COLUMN indicator_id DROP NOT NULL');
            }
            if (!Schema::hasColumn('process_objectives', 'indicator_name')) {
                DB::statement('ALTER TABLE process_objectives ADD COLUMN indicator_name VARCHAR(255) NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('process_objectives') && Schema::hasColumn('process_objectives', 'indicator_name')) {
            DB::statement('ALTER TABLE process_objectives DROP COLUMN indicator_name');
        }
    }
};
