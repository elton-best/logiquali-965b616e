<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute scheduled_date à maintenance_plans et calibration_plans.
     *
     * Ces tables utilisent next_maintenance_date / next_calibration_date comme
     * date de planification principale. Le code (MyTasksController, PlanSMController)
     * filtre sur scheduled_date. On ajoute la colonne et on la backfille depuis
     * la colonne existante pour ne pas casser les données existantes.
     */
    public function up(): void
    {
        // --- maintenance_plans ---
        if (!Schema::hasColumn('maintenance_plans', 'scheduled_date')) {
            Schema::table('maintenance_plans', function (Blueprint $table) {
                $table->date('scheduled_date')->nullable()->after('next_maintenance_date');
            });

            // Backfill: scheduled_date = next_maintenance_date
            DB::statement('UPDATE maintenance_plans SET scheduled_date = next_maintenance_date WHERE scheduled_date IS NULL');
        }

        // --- calibration_plans ---
        if (!Schema::hasColumn('calibration_plans', 'scheduled_date')) {
            Schema::table('calibration_plans', function (Blueprint $table) {
                $table->date('scheduled_date')->nullable()->after('next_calibration_date');
            });

            // Backfill: scheduled_date = next_calibration_date
            DB::statement('UPDATE calibration_plans SET scheduled_date = next_calibration_date WHERE scheduled_date IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('maintenance_plans', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_plans', 'scheduled_date')) {
                $table->dropColumn('scheduled_date');
            }
        });

        Schema::table('calibration_plans', function (Blueprint $table) {
            if (Schema::hasColumn('calibration_plans', 'scheduled_date')) {
                $table->dropColumn('scheduled_date');
            }
        });
    }
};
