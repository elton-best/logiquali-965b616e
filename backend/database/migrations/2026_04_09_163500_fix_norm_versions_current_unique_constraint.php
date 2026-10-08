<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if (!DB::getSchemaBuilder()->hasTable('norm_versions')) {
                return;
            }

            // Drop the incorrect composite uniqueness (norm_id, is_current),
            // which allows at most one "false" row per norm.
            DB::statement('ALTER TABLE norm_versions DROP CONSTRAINT IF EXISTS unique_current_version');
            DB::statement('DROP INDEX IF EXISTS unique_current_version');

            // Defensive repair: when historical data contains several current versions,
            // keep the latest one as current and demote the others.
            DB::statement(
                'WITH latest_current AS (
                    SELECT norm_id, MAX(id) AS keep_id
                    FROM norm_versions
                    WHERE is_current = true
                    GROUP BY norm_id
                 )
                 UPDATE norm_versions nv
                 SET is_current = false
                 FROM latest_current lc
                 WHERE nv.norm_id = lc.norm_id
                   AND nv.is_current = true
                   AND nv.id <> lc.keep_id'
            );

            // Enforce only one current version per norm.
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS norm_versions_one_current_per_norm_idx
                 ON norm_versions (norm_id)
                 WHERE is_current = true'
            );

            return;
        }

        // Fallback for non-PostgreSQL environments:
        // remove hard uniqueness to avoid blocking imports;
        // current version consistency is handled in application logic.
        try {
            DB::statement('ALTER TABLE norm_versions DROP INDEX unique_current_version');
        } catch (\Throwable $e) {
            // Ignore when index/constraint does not exist or syntax differs.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS norm_versions_one_current_per_norm_idx');
            DB::statement(
                'ALTER TABLE norm_versions
                 ADD CONSTRAINT unique_current_version UNIQUE (norm_id, is_current)'
            );
            return;
        }

        try {
            DB::statement('ALTER TABLE norm_versions ADD UNIQUE KEY unique_current_version (norm_id, is_current)');
        } catch (\Throwable $e) {
            // Ignore if already exists or unsupported.
        }
    }
};
