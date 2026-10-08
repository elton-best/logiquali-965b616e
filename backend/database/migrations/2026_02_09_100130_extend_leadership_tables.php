<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // job_descriptions extensions
        if (!Schema::hasColumn('job_descriptions', 'enterprise_id')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('job_descriptions', 'department')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->string('department', 255)->nullable()->after('job_title');
                $table->foreignId('reports_to_id')->nullable()->after('department')->constrained('users')->nullOnDelete();
                $table->jsonb('main_activities')->nullable()->after('mission');
                $table->jsonb('secondary_activities')->nullable()->after('main_activities');
                $table->jsonb('internal_relations')->nullable()->after('secondary_activities');
                $table->jsonb('external_relations')->nullable()->after('internal_relations');
                $table->string('work_location', 255)->nullable()->after('external_relations');
                $table->string('work_schedule', 255)->nullable()->after('work_location');
                $table->boolean('travel_required')->default(false)->after('work_schedule');
                $table->text('physical_requirements')->nullable()->after('travel_required');
                // $table->jsonb('required_skills')->nullable()->after('physical_requirements');
                $table->string('required_experience', 255)->nullable()->after('required_skills');
                $table->string('required_education', 255)->nullable()->after('required_experience');
                $table->jsonb('certifications_required')->nullable()->after('required_education');
                $table->text('employee_signature_data')->nullable()->after('certifications_required');
                $table->timestamp('employee_signed_at')->nullable()->after('employee_signature_data');
                $table->text('manager_signature_data')->nullable()->after('employee_signed_at');
                $table->timestamp('manager_signed_at')->nullable()->after('manager_signature_data');
            });
        }

        // responsibilities extensions
        if (!Schema::hasColumn('responsibilities', 'enterprise_id')) {
            Schema::table('responsibilities', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('responsibilities', 'responsible_type')) {
            Schema::table('responsibilities', function (Blueprint $table) {
                $table->string('responsible_type', 100)->nullable()->after('enterprise_id');
                $table->unsignedBigInteger('responsible_id')->nullable()->after('responsible_type');
                $table->string('role_title', 255)->nullable()->after('user_id');
                $table->jsonb('responsibilities')->nullable()->after('role_title');
                $table->jsonb('authorities')->nullable()->after('responsibilities');
                $table->date('start_date')->nullable()->after('authorities');
                $table->date('end_date')->nullable()->after('start_date');
                $table->index(['responsible_type', 'responsible_id'], 'responsibilities_type_idx');
            });
        }

        // stakeholders extensions
        if (!Schema::hasColumn('stakeholders', 'enterprise_id')) {
            Schema::table('stakeholders', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
            });
        }
        /* if (!Schema::hasColumn('stakeholders', 'category')) {
            Schema::table('stakeholders', function (Blueprint $table) {
                $table->string('category', 100)->nullable()->after('type');
                $table->string('relevance_level', 50)->nullable()->after('relevance_degree');
                $table->text('relevance_justification')->nullable()->after('relevance_level');
                $table->jsonb('needs')->nullable()->after('relevance_justification');
                // $table->jsonb('requirements')->nullable()->after('needs');
                $table->jsonb('actions')->nullable()->after('requirements');
                $table->unsignedBigInteger('responsible_user_id')->nullable()->after('actions');
                $table->date('deadline')->nullable()->after('responsible_user_id');
                $table->jsonb('compliance_requirement_ids')->nullable()->after('deadline');
                $table->jsonb('applicable_norms')->nullable()->after('compliance_requirement_ids');
                $table->index('enterprise_id');
                $table->index('type');
            });
        } */
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_descriptions', 'enterprise_id')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            });
        }
        if (Schema::hasColumn('job_descriptions', 'department')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->dropForeign(['reports_to_id']);
                $table->dropColumn([
                    'department', 'reports_to_id', 'main_activities', 'secondary_activities',
                    'internal_relations', 'external_relations', 'work_location', 'work_schedule',
                    'travel_required', 'physical_requirements', 'required_skills', 'required_experience',
                    'required_education', 'certifications_required', 'employee_signature_data',
                    'employee_signed_at', 'manager_signature_data', 'manager_signed_at'
                ]);
            });
        }

        if (Schema::hasColumn('responsibilities', 'enterprise_id')) {
            Schema::table('responsibilities', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            });
        }
        if (Schema::hasColumn('responsibilities', 'responsible_type')) {
            Schema::table('responsibilities', function (Blueprint $table) {
                $table->dropIndex('responsibilities_type_idx');
                $table->dropColumn([
                    'responsible_type', 'responsible_id', 'role_title', 'responsibilities',
                    'authorities', 'start_date', 'end_date'
                ]);
            });
        }

        if (Schema::hasColumn('stakeholders', 'enterprise_id')) {
            Schema::table('stakeholders', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            });
        }
        if (Schema::hasColumn('stakeholders', 'category')) {
            Schema::table('stakeholders', function (Blueprint $table) {
                $table->dropColumn([
                    'category', 'relevance_level', 'relevance_justification', 'needs', 'requirements',
                    'actions', 'responsible_user_id', 'deadline', 'compliance_requirement_ids',
                    'applicable_norms'
                ]);
            });
        }
    }
};
