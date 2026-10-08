<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('requirement_type', 100);
            $table->string('title', 255);
            $table->string('reference', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('source', 255)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('publication_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->jsonb('applicable_to')->nullable();
            $table->jsonb('applicable_norms')->nullable();
            $table->string('compliance_status', 50)->nullable();
            $table->date('last_evaluation_date')->nullable();
            $table->date('next_evaluation_date')->nullable();
            $table->jsonb('action_ids')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('compliance_status');
        });

        Schema::create('environmental_aspects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('aspect_number', 50)->nullable()->unique();
            $table->string('activity', 255);
            $table->string('aspect', 255);
            $table->string('impact', 255);
            $table->string('condition_type', 50)->nullable();
            $table->integer('frequency_score')->nullable();
            $table->integer('severity_score')->nullable();
            $table->integer('regulatory_score')->nullable();
            $table->integer('significance_score')->nullable();
            $table->boolean('is_significant')->nullable();
            $table->text('control_measures')->nullable();
            $table->jsonb('action_ids')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('is_significant');
        });

        Schema::create('duerp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('version', 50)->default('1.0');
            $table->boolean('is_current')->default(true);
            $table->date('evaluation_date');
            $table->date('next_evaluation_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('is_current');
        });

        Schema::create('duerp_dangers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duerp_id')->constrained('duerp')->cascadeOnDelete();
            $table->string('organizational_unit', 255)->nullable();
            $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->string('activity', 255)->nullable();
            $table->string('danger_type', 100);
            $table->text('danger_description');
            $table->jsonb('exposed_workers')->nullable();
            $table->integer('probability_score')->nullable();
            $table->integer('severity_score')->nullable();
            $table->integer('criticality_score')->nullable();
            $table->string('criticality_level', 50)->nullable();
            $table->text('existing_measures')->nullable();
            $table->jsonb('actions')->nullable();
            $table->jsonb('applicable_norms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('duerp_id');
            $table->index('criticality_level');
        });

        // Extend risks
        if (!Schema::hasColumn('risks', 'risk_number')) {
            Schema::table('risks', function (Blueprint $table) {
                $table->string('risk_number', 50)->nullable()->after('ref');
                $table->text('causes')->nullable()->after('root_cause');
                $table->integer('probability_score')->nullable()->after('probability');
                $table->integer('severity_score')->nullable()->after('gravity');
                $table->integer('criticality_score')->nullable()->after('criticality');
                // $table->string('criticality_level', 50)->nullable()->after('criticality_score');
                $table->jsonb('applicable_norms')->nullable()->after('criticality_level');
                $table->jsonb('action_ids')->nullable()->after('applicable_norms');
                $table->foreignId('responsible_user_id')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
                $table->string('follow_up_status', 50)->nullable()->after('deadline');
            });
        }

        // Extend opportunities
        if (!Schema::hasColumn('opportunities', 'enterprise_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
                $table->string('opportunity_number', 50)->nullable()->after('enterprise_id');
                // $table->string('title', 255)->nullable()->after('opportunity_number');
                $table->string('potential_impact', 50)->nullable()->after('description');
                $table->integer('feasibility_score')->nullable()->after('potential_impact');
                $table->string('priority_level', 50)->nullable()->after('feasibility_score');
                $table->jsonb('action_ids')->nullable()->after('priority_level');
                $table->foreignId('responsible_user_id')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
                $table->jsonb('applicable_norms')->nullable()->after('status');
            });
        }

        // Extend objectives
        if (!Schema::hasColumn('objectives', 'strategic_axis_id')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->foreignId('strategic_axis_id')->nullable()->after('site_id')->constrained('strategic_axes')->nullOnDelete();
                $table->string('measurement_frequency', 50)->nullable()->after('target_value');
                $table->text('required_resources')->nullable()->after('measurement_frequency');
                $table->jsonb('applicable_norms')->nullable()->after('required_resources');
            });
        }

        // Extend indicateurs
        if (!Schema::hasColumn('indicateurs', 'calculation_formula')) {
            Schema::table('indicateurs', function (Blueprint $table) {
                $table->text('calculation_formula')->nullable()->after('description');
                $table->decimal('threshold_green', 10, 2)->nullable()->after('calculation_formula');
                $table->decimal('threshold_orange', 10, 2)->nullable()->after('threshold_green');
                $table->decimal('threshold_red', 10, 2)->nullable()->after('threshold_orange');
                $table->string('measurement_unit', 100)->nullable()->after('threshold_red');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('duerp_dangers');
        Schema::dropIfExists('duerp');
        Schema::dropIfExists('environmental_aspects');
        Schema::dropIfExists('compliance_requirements');

        if (Schema::hasColumn('risks', 'risk_number')) {
            Schema::table('risks', function (Blueprint $table) {
                $table->dropColumn([
                    'risk_number', 'causes', 'probability_score', 'severity_score',
                    'criticality_score', 'criticality_level', 'applicable_norms',
                    'action_ids', 'responsible_user_id', 'follow_up_status'
                ]);
            });
        }

        if (Schema::hasColumn('opportunities', 'enterprise_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->dropColumn([
                    'enterprise_id', 'opportunity_number', 'title', 'potential_impact',
                    'feasibility_score', 'priority_level', 'action_ids',
                    'responsible_user_id', 'applicable_norms'
                ]);
            });
        }

        if (Schema::hasColumn('objectives', 'strategic_axis_id')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->dropColumn([
                    'strategic_axis_id', 'measurement_frequency', 'required_resources',
                    'applicable_norms'
                ]);
            });
        }

        if (Schema::hasColumn('indicateurs', 'calculation_formula')) {
            Schema::table('indicateurs', function (Blueprint $table) {
                $table->dropColumn([
                    'calculation_formula', 'threshold_green', 'threshold_orange',
                    'threshold_red', 'measurement_unit'
                ]);
            });
        }
    }
};
