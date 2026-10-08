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
        // 1. Update duerp table with workflow fields
        Schema::table('duerp', function (Blueprint $table) {
            if (!Schema::hasColumn('duerp', 'title')) {
                $table->string('title', 255)->default('Document Unique d\'Évaluation des Risques Professionnels')->after('version');
            }
            if (!Schema::hasColumn('duerp', 'workflow_status')) {
                $table->string('workflow_status', 50)->default('draft')->after('is_current'); // draft, verified_by_rq, approved_by_ceo
            }
            if (!Schema::hasColumn('duerp', 'work_unit_definition')) {
                $table->string('work_unit_definition', 50)->default('process')->after('workflow_status'); // process, site, custom (REQ-6.1-D01)
            }
            if (!Schema::hasColumn('duerp', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('next_evaluation_date');
            }
            if (!Schema::hasColumn('duerp', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('duerp', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');
            }
            if (!Schema::hasColumn('duerp', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });

        // 2. Update duerp_dangers table with INRS families & exact Excel canevas fields
        Schema::table('duerp_dangers', function (Blueprint $table) {
            if (!Schema::hasColumn('duerp_dangers', 'work_unit')) {
                $table->string('work_unit', 255)->nullable()->after('process_id'); // Poste de travail / Unité
            }
            if (!Schema::hasColumn('duerp_dangers', 'inrs_family')) {
                $table->string('inrs_family', 255)->nullable()->after('activity'); // Famille INRS
            }
            if (!Schema::hasColumn('duerp_dangers', 'dangerous_situation')) {
                $table->text('dangerous_situation')->nullable()->after('inrs_family');
            }
            if (!Schema::hasColumn('duerp_dangers', 'identified_risks')) {
                $table->text('identified_risks')->nullable()->after('dangerous_situation');
            }
            if (!Schema::hasColumn('duerp_dangers', 'consequences')) {
                $table->text('consequences')->nullable()->after('identified_risks');
            }
            if (!Schema::hasColumn('duerp_dangers', 'gravity')) {
                $table->unsignedInteger('gravity')->default(1)->after('consequences'); // 1 à 4
            }
            if (!Schema::hasColumn('duerp_dangers', 'frequency')) {
                $table->unsignedInteger('frequency')->default(1)->after('gravity'); // 1 à 4
            }
            if (!Schema::hasColumn('duerp_dangers', 'raw_risk_score')) {
                $table->unsignedInteger('raw_risk_score')->nullable()->after('frequency'); // G * F
            }
            if (!Schema::hasColumn('duerp_dangers', 'raw_risk_level')) {
                $table->string('raw_risk_level', 50)->nullable()->after('raw_risk_score'); // Faible, Moyen, Grave
            }
            if (!Schema::hasColumn('duerp_dangers', 'existing_preventions')) {
                $table->text('existing_preventions')->nullable()->after('raw_risk_level');
            }
            if (!Schema::hasColumn('duerp_dangers', 'prevention_actions')) {
                $table->text('prevention_actions')->nullable()->after('existing_preventions');
            }
            if (!Schema::hasColumn('duerp_dangers', 'responsible_id')) {
                $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete()->after('prevention_actions');
            }
            if (!Schema::hasColumn('duerp_dangers', 'responsible_name')) {
                $table->string('responsible_name', 255)->nullable()->after('responsible_id');
            }
            if (!Schema::hasColumn('duerp_dangers', 'deadline')) {
                $table->date('deadline')->nullable()->after('responsible_name');
            }
            if (!Schema::hasColumn('duerp_dangers', 'action_status')) {
                $table->string('action_status', 50)->default('en_cours')->after('deadline'); // en_continu, en_cours, realise
            }
            if (!Schema::hasColumn('duerp_dangers', 'residual_gravity')) {
                $table->unsignedInteger('residual_gravity')->nullable()->after('action_status');
            }
            if (!Schema::hasColumn('duerp_dangers', 'residual_frequency')) {
                $table->unsignedInteger('residual_frequency')->nullable()->after('residual_gravity');
            }
            if (!Schema::hasColumn('duerp_dangers', 'residual_risk_score')) {
                $table->unsignedInteger('residual_risk_score')->nullable()->after('residual_frequency');
            }
            if (!Schema::hasColumn('duerp_dangers', 'residual_risk_level')) {
                $table->string('residual_risk_level', 50)->nullable()->after('residual_risk_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duerp_dangers', function (Blueprint $table) {
            $table->dropColumn([
                'work_unit',
                'inrs_family',
                'dangerous_situation',
                'identified_risks',
                'consequences',
                'gravity',
                'frequency',
                'raw_risk_score',
                'raw_risk_level',
                'existing_preventions',
                'prevention_actions',
                'responsible_id',
                'responsible_name',
                'deadline',
                'action_status',
                'residual_gravity',
                'residual_frequency',
                'residual_risk_score',
                'residual_risk_level',
            ]);
        });

        Schema::table('duerp', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'workflow_status',
                'work_unit_definition',
                'verified_by',
                'verified_at',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};

