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
        Schema::table('management_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('management_reviews', 'resources_data')) {
                $table->jsonb('resources_data')->nullable()->after('resources_adequacy');
            }
            if (!Schema::hasColumn('management_reviews', 'system_changes_data')) {
                $table->jsonb('system_changes_data')->nullable()->after('output_decisions');
            }
            if (!Schema::hasColumn('management_reviews', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('actual_date');
            }
            if (!Schema::hasColumn('management_reviews', 'opened_by_user_id')) {
                $table->foreignId('opened_by_user_id')->nullable()->after('opened_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('management_reviews', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('opened_by_user_id');
            }
            if (!Schema::hasColumn('management_reviews', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('management_reviews', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['resources_data', 'system_changes_data', 'opened_at', 'opened_by_user_id', 'closed_at', 'closed_by_user_id'] as $col) {
                if (Schema::hasColumn('management_reviews', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

