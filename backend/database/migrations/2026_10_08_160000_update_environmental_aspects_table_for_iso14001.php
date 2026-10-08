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
        Schema::table('environmental_aspects', function (Blueprint $table) {
            if (!Schema::hasColumn('environmental_aspects', 'sub_process')) {
                $table->string('sub_process', 255)->nullable()->after('process_id');
            }
            if (!Schema::hasColumn('environmental_aspects', 'mode')) {
                $table->string('mode', 20)->default('N')->after('sub_process'); // N: Normal, A: Accidentel / Dégradé
            }
            if (!Schema::hasColumn('environmental_aspects', 'risk')) {
                $table->text('risk')->nullable()->after('impact');
            }
            if (!Schema::hasColumn('environmental_aspects', 'existing_controls')) {
                $table->text('existing_controls')->nullable()->after('risk');
            }
            if (!Schema::hasColumn('environmental_aspects', 'gravity')) {
                $table->unsignedInteger('gravity')->default(1)->after('existing_controls'); // 1 à 5
            }
            if (!Schema::hasColumn('environmental_aspects', 'frequency')) {
                $table->unsignedInteger('frequency')->default(1)->after('gravity'); // 1 à 7
            }
            if (!Schema::hasColumn('environmental_aspects', 'sensitivity')) {
                $table->unsignedInteger('sensitivity')->default(1)->after('frequency'); // 1 à 5
            }
            if (!Schema::hasColumn('environmental_aspects', 'mastery')) {
                $table->unsignedInteger('mastery')->default(1)->after('sensitivity'); // 1 à 5
            }
            if (!Schema::hasColumn('environmental_aspects', 'criticality_score')) {
                $table->unsignedInteger('criticality_score')->nullable()->after('mastery'); // G * F * S * M
            }
            if (!Schema::hasColumn('environmental_aspects', 'significance_threshold')) {
                $table->unsignedInteger('significance_threshold')->default(343)->after('criticality_score'); // Seuil 343
            }
            if (!Schema::hasColumn('environmental_aspects', 'additional_actions')) {
                $table->text('additional_actions')->nullable()->after('control_measures');
            }
            if (!Schema::hasColumn('environmental_aspects', 'responsible_id')) {
                $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete()->after('additional_actions');
            }
            if (!Schema::hasColumn('environmental_aspects', 'responsible_name')) {
                $table->string('responsible_name', 255)->nullable()->after('responsible_id');
            }
            if (!Schema::hasColumn('environmental_aspects', 'deadline')) {
                $table->date('deadline')->nullable()->after('responsible_name');
            }
            if (!Schema::hasColumn('environmental_aspects', 'effectiveness_criterion')) {
                $table->text('effectiveness_criterion')->nullable()->after('deadline');
            }
            if (!Schema::hasColumn('environmental_aspects', 'efficiency_criterion')) {
                $table->text('efficiency_criterion')->nullable()->after('effectiveness_criterion');
            }
            if (!Schema::hasColumn('environmental_aspects', 'observations')) {
                $table->text('observations')->nullable()->after('efficiency_criterion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environmental_aspects', function (Blueprint $table) {
            $table->dropColumn([
                'sub_process',
                'mode',
                'risk',
                'existing_controls',
                'gravity',
                'frequency',
                'sensitivity',
                'mastery',
                'criticality_score',
                'significance_threshold',
                'additional_actions',
                'responsible_id',
                'responsible_name',
                'deadline',
                'effectiveness_criterion',
                'efficiency_criterion',
                'observations',
            ]);
        });
    }
};

