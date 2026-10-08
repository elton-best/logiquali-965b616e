<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('processes', 'enterprise_id')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('ref')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            });
        }

        // Backfill existing rows from related site.
        DB::statement('
            UPDATE processes p
            SET enterprise_id = s.enterprise_id
            FROM sites s
            WHERE p.site_id = s.id
              AND p.enterprise_id IS NULL
        ');

        if (!Schema::hasColumn('processes', 'enterprise_id')) {
            return;
        }

        // Index for tenant-filtered queries.
        Schema::table('processes', function (Blueprint $table) {
            $table->index('enterprise_id', 'processes_enterprise_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('processes', 'enterprise_id')) {
            return;
        }

        Schema::table('processes', function (Blueprint $table) {
            $table->dropIndex('processes_enterprise_id_index');
            $table->dropConstrainedForeignId('enterprise_id');
        });
    }
};

