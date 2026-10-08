<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('process_reviews', 'title')) {
                $table->string('title')->nullable()->after('process_id');
            }
            if (!Schema::hasColumn('process_reviews', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('led_by')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('process_reviews', 'coverage_start_date')) {
                $table->date('coverage_start_date')->nullable()->after('next_review_date');
            }
            if (!Schema::hasColumn('process_reviews', 'coverage_end_date')) {
                $table->date('coverage_end_date')->nullable()->after('coverage_start_date');
            }
            if (!Schema::hasColumn('process_reviews', 'started_at')) {
                $table->dateTime('started_at')->nullable()->after('coverage_end_date');
            }
            if (!Schema::hasColumn('process_reviews', 'ended_at')) {
                $table->dateTime('ended_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('process_reviews', 'participants_presence')) {
                $table->json('participants_presence')->nullable()->after('participants');
            }
            if (!Schema::hasColumn('process_reviews', 'role_assignments')) {
                $table->json('role_assignments')->nullable()->after('participants_presence');
            }
            if (!Schema::hasColumn('process_reviews', 'action_responsibles')) {
                $table->json('action_responsibles')->nullable()->after('role_assignments');
            }
            if (!Schema::hasColumn('process_reviews', 'other_participants')) {
                $table->text('other_participants')->nullable()->after('action_responsibles');
            }

            if (!Schema::hasColumn('process_reviews', 'synthesis_data')) {
                $table->json('synthesis_data')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('process_reviews', 'pip_data')) {
                $table->json('pip_data')->nullable()->after('synthesis_data');
            }
            if (!Schema::hasColumn('process_reviews', 'risk_data')) {
                $table->json('risk_data')->nullable()->after('pip_data');
            }
            if (!Schema::hasColumn('process_reviews', 'opportunity_data')) {
                $table->json('opportunity_data')->nullable()->after('risk_data');
            }
            if (!Schema::hasColumn('process_reviews', 'quality_objectives_data')) {
                $table->json('quality_objectives_data')->nullable()->after('opportunity_data');
            }
            if (!Schema::hasColumn('process_reviews', 'quality_activities_data')) {
                $table->json('quality_activities_data')->nullable()->after('quality_objectives_data');
            }
            if (!Schema::hasColumn('process_reviews', 'operational_activities_data')) {
                $table->json('operational_activities_data')->nullable()->after('quality_activities_data');
            }
            if (!Schema::hasColumn('process_reviews', 'compliance_data')) {
                $table->json('compliance_data')->nullable()->after('operational_activities_data');
            }
            if (!Schema::hasColumn('process_reviews', 'non_conformity_data')) {
                $table->json('non_conformity_data')->nullable()->after('compliance_data');
            }
            if (!Schema::hasColumn('process_reviews', 'leadership_data')) {
                $table->json('leadership_data')->nullable()->after('non_conformity_data');
            }
            if (!Schema::hasColumn('process_reviews', 'duerp_data')) {
                $table->json('duerp_data')->nullable()->after('leadership_data');
            }
            if (!Schema::hasColumn('process_reviews', 'other_notes')) {
                $table->text('other_notes')->nullable()->after('duerp_data');
            }
            if (!Schema::hasColumn('process_reviews', 'conclusion')) {
                $table->text('conclusion')->nullable()->after('other_notes');
            }

            if (!Schema::hasColumn('process_reviews', 'report_pdf_path')) {
                $table->string('report_pdf_path')->nullable()->after('conclusion');
            }
            if (!Schema::hasColumn('process_reviews', 'report_docx_path')) {
                $table->string('report_docx_path')->nullable()->after('report_pdf_path');
            }
            if (!Schema::hasColumn('process_reviews', 'report_generated_at')) {
                $table->dateTime('report_generated_at')->nullable()->after('report_docx_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('process_reviews', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }

            $columns = [
                'title',
                'coverage_start_date',
                'coverage_end_date',
                'started_at',
                'ended_at',
                'participants_presence',
                'role_assignments',
                'action_responsibles',
                'other_participants',
                'synthesis_data',
                'pip_data',
                'risk_data',
                'opportunity_data',
                'quality_objectives_data',
                'quality_activities_data',
                'operational_activities_data',
                'compliance_data',
                'non_conformity_data',
                'leadership_data',
                'duerp_data',
                'other_notes',
                'conclusion',
                'report_pdf_path',
                'report_docx_path',
                'report_generated_at',
            ];

            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('process_reviews', $column)));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
