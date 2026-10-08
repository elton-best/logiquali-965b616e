<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('process_sequences', 'sub_activities')) {
            Schema::table('process_sequences', function (Blueprint $table) {
                $table->jsonb('sub_activities')->nullable()->after('activity_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('process_sequences', 'sub_activities')) {
            Schema::table('process_sequences', function (Blueprint $table) {
                $table->dropColumn('sub_activities');
            });
        }
    }
};
