<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add FK if it doesn't already exist
        if (!Schema::hasColumn('norms', 'current_version_id')) {
            Schema::table('norms', function (Blueprint $table) {
                $table->foreignId('current_version_id')->nullable()->after('domain')->constrained('norm_versions')->nullOnDelete();
            });
        } else {
            // Column exists, just add FK
            Schema::table('norms', function (Blueprint $table) {
                if (!$this->hasForeignKey('norms', 'current_version_id')) {
                    $table->foreign('current_version_id')->references('id')->on('norm_versions')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('norms', function (Blueprint $table) {
            if ($this->hasForeignKey('norms', 'current_version_id')) {
                $table->dropForeign(['current_version_id']);
            }
        });
    }

    private function hasForeignKey($table, $column)
    {
        $foreignKeys = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_NAME LIKE '%foreign'",
            [$table, $column]
        );
        return count($foreignKeys) > 0;
    }
};
