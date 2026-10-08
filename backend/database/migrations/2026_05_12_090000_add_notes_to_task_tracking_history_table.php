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
        Schema::table('task_tracking_history', function (Blueprint $table) {
            if (!Schema::hasColumn('task_tracking_history', 'notes')) {
                $table->text('notes')->nullable()->after('progress_rate_new');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_tracking_history', function (Blueprint $table) {
            if (Schema::hasColumn('task_tracking_history', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
