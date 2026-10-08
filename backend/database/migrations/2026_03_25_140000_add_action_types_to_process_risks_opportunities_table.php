<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('process_risks_opportunities', 'action_types')) {
                $table->json('action_types')->nullable()->after('planned_actions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('process_risks_opportunities', 'action_types')) {
                $table->dropColumn('action_types');
            }
        });
    }
};
