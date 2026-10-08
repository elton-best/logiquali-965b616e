<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes manquantes à operational_project_activities
     * et operational_project_tasks :
     *   - enterprise_id  : pour le filtre multi-tenant
     *   - assigned_user_ids : liste JSON des utilisateurs assignés (en plus du responsable)
     *   - assigned_to    : sur tasks uniquement (assignation principale, alias de responsible_user_id)
     *
     * Ces colonnes sont utilisées dans MyTasksController et PlanSMController.
     */
    public function up(): void
    {
        // --- operational_project_activities ---
        Schema::table('operational_project_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('operational_project_activities', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('operational_project_activities', 'assigned_user_ids')) {
                $table->json('assigned_user_ids')->nullable()->after('responsible_user_id');
            }
        });

        // --- operational_project_tasks ---
        Schema::table('operational_project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('operational_project_tasks', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('activity_id')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('operational_project_tasks', 'assigned_user_ids')) {
                $table->json('assigned_user_ids')->nullable()->after('responsible_user_id');
            }

            if (!Schema::hasColumn('operational_project_tasks', 'assigned_to')) {
                $table->foreignId('assigned_to')
                    ->nullable()
                    ->after('responsible_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('operational_project_activities', function (Blueprint $table) {
            if (Schema::hasColumn('operational_project_activities', 'enterprise_id')) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            }
            if (Schema::hasColumn('operational_project_activities', 'assigned_user_ids')) {
                $table->dropColumn('assigned_user_ids');
            }
        });

        Schema::table('operational_project_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('operational_project_tasks', 'enterprise_id')) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            }
            if (Schema::hasColumn('operational_project_tasks', 'assigned_user_ids')) {
                $table->dropColumn('assigned_user_ids');
            }
            if (Schema::hasColumn('operational_project_tasks', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });
    }
};
