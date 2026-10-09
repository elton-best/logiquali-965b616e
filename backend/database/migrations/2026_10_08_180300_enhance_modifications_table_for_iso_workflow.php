<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-6.3-01..05 / REQ-7.5-03 : Circuit des modifications du système (Brouillon -> RQ -> CEO).
     */
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            if (!Schema::hasColumn('modifications', 'enterprise_id')) {
                $table->foreignId('enterprise_id')->nullable()->after('id')->constrained('enterprises')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('modifications', 'initiator_id')) {
                $table->foreignId('initiator_id')->nullable()->after('site_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('modifications', 'affected_document_ids')) {
                $table->json('affected_document_ids')->nullable()->after('required_resources');
            }
            if (!Schema::hasColumn('modifications', 'workflow_status')) {
                $table->string('workflow_status', 30)->default('brouillon')->after('status'); // brouillon, en_cours, verifie_rq, approuve_ceo, rejete
            }
            if (!Schema::hasColumn('modifications', 'rq_verified_by')) {
                $table->foreignId('rq_verified_by')->nullable()->after('workflow_status')->constrained('users')->nullOnDelete();
                $table->timestamp('rq_verified_at')->nullable()->after('rq_verified_by');
                $table->text('rq_notes')->nullable()->after('rq_verified_at');
            }
            if (!Schema::hasColumn('modifications', 'ceo_approved_by')) {
                $table->foreignId('ceo_approved_by')->nullable()->after('rq_notes')->constrained('users')->nullOnDelete();
                $table->timestamp('ceo_approved_at')->nullable()->after('ceo_approved_by');
                $table->text('ceo_notes')->nullable()->after('ceo_approved_at');
            }
            if (!Schema::hasColumn('modifications', 'modification_results')) {
                $table->text('modification_results')->nullable()->after('ceo_notes');
            }
            if (!Schema::hasColumn('modifications', 'surveillance_results')) {
                $table->text('surveillance_results')->nullable()->after('modification_results');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->dropColumn([
                'enterprise_id',
                'initiator_id',
                'affected_document_ids',
                'workflow_status',
                'rq_verified_by',
                'rq_verified_at',
                'rq_notes',
                'ceo_approved_by',
                'ceo_approved_at',
                'ceo_notes',
                'modification_results',
                'surveillance_results',
            ]);
        });
    }
};

