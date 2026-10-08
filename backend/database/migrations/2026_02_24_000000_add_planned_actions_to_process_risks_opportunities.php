<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('process_risks_opportunities', 'planned_actions')) {
                $table->json('planned_actions')->nullable()->after('actions_prevues');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('process_risks_opportunities', 'planned_actions')) {
                $table->dropColumn('planned_actions');
            }
        });
    }
};
