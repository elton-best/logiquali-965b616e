<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_reviews', function (Blueprint $table): void {
            if (!Schema::hasColumn('process_reviews', 'site_id')) {
                $table->foreignId('site_id')->nullable()->after('process_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('process_reviews', 'identification')) {
                $table->json('identification')->nullable()->after('participants');
            }

            if (!Schema::hasColumn('process_reviews', 'sections')) {
                $table->json('sections')->nullable()->after('identification');
            }

            if (!Schema::hasColumn('process_reviews', 'metrics_snapshot')) {
                $table->json('metrics_snapshot')->nullable()->after('sections');
            }

            if (!Schema::hasColumn('process_reviews', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('process_reviews', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('process_reviews', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('ended_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('process_reviews', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }

            $columns = ['ended_at', 'started_at', 'metrics_snapshot', 'sections', 'identification'];
            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('process_reviews', $column)));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }

            if (Schema::hasColumn('process_reviews', 'site_id')) {
                $table->dropConstrainedForeignId('site_id');
            }
        });
    }
};
