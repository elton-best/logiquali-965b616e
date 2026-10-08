<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // Ajouter site_id si pas déjà présent
            if (!Schema::hasColumn('objectives', 'site_id')) {
                $table->foreignId('site_id')->after('id')->constrained()->cascadeOnDelete();
            }
            
            // Enrichir les champs indicateur
            if (!Schema::hasColumn('objectives', 'indicator_name')) {
                $table->string('indicator_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('objectives', 'indicator_formula')) {
                $table->text('indicator_formula')->nullable()->after('indicator_name');
            }
            if (!Schema::hasColumn('objectives', 'unit')) {
                $table->string('unit', 50)->nullable()->after('indicator_formula');
            }
            if (!Schema::hasColumn('objectives', 'measurement_frequency')) {
                $table->string('measurement_frequency', 50)->nullable()->after('unit');
            }
            if (!Schema::hasColumn('objectives', 'data_source')) {
                $table->string('data_source')->nullable()->after('measurement_frequency');
            }
            
            // Ajouter valeurs de référence et seuils
            if (!Schema::hasColumn('objectives', 'baseline_value')) {
                $table->decimal('baseline_value', 10, 2)->nullable()->after('data_source');
            }
            if (!Schema::hasColumn('objectives', 'threshold_alert')) {
                $table->decimal('threshold_alert', 10, 2)->nullable()->after('target_value');
            }
            if (!Schema::hasColumn('objectives', 'threshold_critical')) {
                $table->decimal('threshold_critical', 10, 2)->nullable()->after('threshold_alert');
            }
            
            // Ajouter dates
            if (!Schema::hasColumn('objectives', 'start_date')) {
                $table->date('start_date')->nullable()->after('threshold_critical');
            }
            
            // Ajouter responsable collecte données
            if (!Schema::hasColumn('objectives', 'data_collector_id')) {
                $table->foreignId('data_collector_id')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
            }
            
            // Ajouter taux de réalisation
            if (!Schema::hasColumn('objectives', 'achievement_rate')) {
                $table->decimal('achievement_rate', 5, 2)->nullable()->after('status');
            }
            
            // Ajouter validation
            if (!Schema::hasColumn('objectives', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('objectives', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn([
                'indicator_name', 'indicator_formula', 'unit', 'measurement_frequency', 'data_source',
                'baseline_value', 'threshold_alert', 'threshold_critical', 'start_date',
                'data_collector_id', 'achievement_rate', 'validated_by', 'validated_at'
            ]);
        });
    }
};
