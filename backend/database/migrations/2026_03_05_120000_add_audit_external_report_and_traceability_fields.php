<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->string('external_report_path')->nullable()->after('report_path');
            $table->enum('report_source', ['auto', 'external'])->default('auto')->after('external_report_path');
            $table->unsignedInteger('report_version')->default(1)->after('report_source');
            $table->timestamp('external_report_uploaded_at')->nullable()->after('report_version');
            $table->json('m9_d2_traceability')->nullable()->after('risk_based_criteria');
            $table->json('m9_d5_traceability')->nullable()->after('m9_d2_traceability');
        });

        Schema::table('management_reviews', function (Blueprint $table) {
            $table->json('m12_d2_traceability')->nullable()->after('audit_data');
            $table->json('m12_d3_traceability')->nullable()->after('m12_d2_traceability');
        });

        Schema::table('actions', function (Blueprint $table) {
            $table->json('m7_d4_traceability')->nullable()->after('progress_notes');
        });

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->json('m13_d3_traceability')->nullable()->after('recommendations');
            $table->json('m13_d4_traceability')->nullable()->after('m13_d3_traceability');
            $table->json('m13_d6_traceability')->nullable()->after('m13_d4_traceability');
        });

        Schema::table('client_satisfaction_forms', function (Blueprint $table) {
            $table->json('m13_d4_traceability')->nullable()->after('recommendations');
            $table->json('m13_d6_traceability')->nullable()->after('m13_d4_traceability');
        });

        Schema::table('employee_evaluations', function (Blueprint $table) {
            $table->json('m13_d3_traceability')->nullable()->after('comments');
            $table->json('m13_d6_traceability')->nullable()->after('m13_d3_traceability');
        });

        Schema::table('auditor_evaluations', function (Blueprint $table) {
            $table->json('m9_d5_traceability')->nullable()->after('action_plan');
        });
    }

    public function down(): void
    {
        Schema::table('auditor_evaluations', function (Blueprint $table) {
            $table->dropColumn(['m9_d5_traceability']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table) {
            $table->dropColumn(['m13_d3_traceability', 'm13_d6_traceability']);
        });

        Schema::table('client_satisfaction_forms', function (Blueprint $table) {
            $table->dropColumn(['m13_d4_traceability', 'm13_d6_traceability']);
        });

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->dropColumn(['m13_d3_traceability', 'm13_d4_traceability', 'm13_d6_traceability']);
        });

        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn(['m7_d4_traceability']);
        });

        Schema::table('management_reviews', function (Blueprint $table) {
            $table->dropColumn(['m12_d2_traceability', 'm12_d3_traceability']);
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn([
                'external_report_path',
                'report_source',
                'report_version',
                'external_report_uploaded_at',
                'm9_d2_traceability',
                'm9_d5_traceability',
            ]);
        });
    }
};
