<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE plans MODIFY status ENUM('draft', 'validated', 'in_progress', 'completed', 'archived') NOT NULL DEFAULT 'draft'"
            );
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE plans DROP CONSTRAINT IF EXISTS plans_status_check');
            DB::statement('ALTER TABLE plans ALTER COLUMN status TYPE VARCHAR(32)');
            DB::statement(
                "ALTER TABLE plans ADD CONSTRAINT plans_status_check CHECK (status IN ('draft', 'validated', 'in_progress', 'completed', 'archived'))"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE plans MODIFY status ENUM('draft', 'validated', 'in_progress', 'completed') NOT NULL DEFAULT 'draft'"
            );
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE plans DROP CONSTRAINT IF EXISTS plans_status_check');
            DB::statement('ALTER TABLE plans ALTER COLUMN status TYPE VARCHAR(32)');
            DB::statement(
                "ALTER TABLE plans ADD CONSTRAINT plans_status_check CHECK (status IN ('draft', 'validated', 'in_progress', 'completed'))"
            );
        }
    }
};
