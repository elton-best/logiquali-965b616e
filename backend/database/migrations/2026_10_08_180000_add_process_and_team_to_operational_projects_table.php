<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-8.6-01 : Projets liés aux processus.
     * REQ-8.6-02 / RT-14 : Équipe projet et responsable projet.
     */
    public function up(): void
    {
        Schema::table('operational_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('operational_projects', 'process_id')) {
                $table->foreignId('process_id')->nullable()->after('site_id')->constrained('processes')->nullOnDelete();
            }
            if (!Schema::hasColumn('operational_projects', 'team_user_ids')) {
                $table->json('team_user_ids')->nullable()->after('project_manager_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_projects', function (Blueprint $table) {
            if (Schema::hasColumn('operational_projects', 'process_id')) {
                $table->dropForeign(['process_id']);
                $table->dropColumn('process_id');
            }
            if (Schema::hasColumn('operational_projects', 'team_user_ids')) {
                $table->dropColumn('team_user_ids');
            }
        });
    }
};

