<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('processes') && !Schema::hasColumn('processes', 'observation')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->text('observation')->nullable()->after('purpose');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('processes') && Schema::hasColumn('processes', 'observation')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->dropColumn('observation');
            });
        }
    }
};

