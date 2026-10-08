<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_conformities', function (Blueprint $table) {
            if (!Schema::hasColumn('non_conformities', 'finding_type')) {
                $table->enum('finding_type', ['non_conformity', 'gap'])
                    ->default('non_conformity')
                    ->after('title');
            }

            if (!Schema::hasColumn('non_conformities', 'detection_source')) {
                $table->enum('detection_source', [
                    'internal_audit',
                    'external_audit',
                    'customer_complaint',
                    'internal_control',
                    'management_review',
                    'other',
                ])->nullable()->after('source');
            }

            if (!Schema::hasColumn('non_conformities', 'requirement_reference')) {
                $table->string('requirement_reference', 255)->nullable()->after('description');
            }

            if (!Schema::hasColumn('non_conformities', 'result_summary')) {
                $table->text('result_summary')->nullable()->after('verification_notes');
            }

            if (!Schema::hasColumn('non_conformities', 'rq_signature_date')) {
                $table->date('rq_signature_date')->nullable()->after('result_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('non_conformities', function (Blueprint $table) {
            $table->dropColumn([
                'finding_type',
                'detection_source',
                'requirement_reference',
                'result_summary',
                'rq_signature_date',
            ]);
        });
    }
};
