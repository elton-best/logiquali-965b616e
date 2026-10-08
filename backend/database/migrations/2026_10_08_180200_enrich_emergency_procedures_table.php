<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-8.2-01 : Situation d'urgence, Mesures, Responsable, Délai.
     * REQ-8.2-02 : Exercices/simulations et comptes rendus.
     */
    public function up(): void
    {
        Schema::table('emergency_procedures', function (Blueprint $table) {
            if (!Schema::hasColumn('emergency_procedures', 'responsible_id')) {
                $table->foreignId('responsible_id')->nullable()->after('site_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('emergency_procedures', 'measures')) {
                $table->text('measures')->nullable()->after('title');
            }
            if (!Schema::hasColumn('emergency_procedures', 'deadline')) {
                $table->date('deadline')->nullable()->after('measures');
            }
            if (!Schema::hasColumn('emergency_procedures', 'drill_report')) {
                $table->text('drill_report')->nullable()->after('next_drill_date');
            }
            if (!Schema::hasColumn('emergency_procedures', 'status')) {
                $table->string('status', 30)->default('operationnel')->after('deadline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_procedures', function (Blueprint $table) {
            if (Schema::hasColumn('emergency_procedures', 'responsible_id')) {
                $table->dropForeign(['responsible_id']);
                $table->dropColumn('responsible_id');
            }
            if (Schema::hasColumn('emergency_procedures', 'measures')) {
                $table->dropColumn('measures');
            }
            if (Schema::hasColumn('emergency_procedures', 'deadline')) {
                $table->dropColumn('deadline');
            }
            if (Schema::hasColumn('emergency_procedures', 'drill_report')) {
                $table->dropColumn('drill_report');
            }
            if (Schema::hasColumn('emergency_procedures', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
