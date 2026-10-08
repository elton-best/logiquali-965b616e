<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Évaluation des compétences auditeurs (ISO 9001 §7.2)
     * + Évaluation post-audit de la performance
     */
    public function up(): void
    {
        Schema::create('auditor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Auditeur évalué
            $table->foreignId('audit_id')->nullable()->constrained()->cascadeOnDelete(); // Si évaluation post-audit
            
            // Type d'évaluation
            $table->enum('type', [
                'competence',      // Évaluation initiale compétence
                'post_audit',      // Évaluation après audit réalisé
                'annual_review'    // Revue annuelle
            ])->default('competence');
            
            // Compétences générales (note /5)
            $table->integer('technical_knowledge')->nullable(); // Connaissance technique métier
            $table->integer('iso_knowledge')->nullable(); // Maîtrise normes ISO
            $table->integer('audit_methodology')->nullable(); // Méthodologie audit
            $table->integer('communication_skills')->nullable(); // Communication
            $table->integer('objectivity')->nullable(); // Objectivité/Indépendance
            $table->integer('report_writing')->nullable(); // Rédaction rapports
            
            // Score global /5
            $table->decimal('overall_score', 3, 2)->nullable();
            
            // Qualifications
            $table->json('certifications')->nullable(); // [{name, organism, date, validity}]
            $table->json('formations')->nullable(); // [{title, date, duration}]
            $table->json('qualified_domains')->nullable(); // ['ISO9001', 'ISO14001', 'ISO45001']
            
            // Expérience
            $table->integer('audits_conducted')->default(0);
            $table->date('first_audit_date')->nullable();
            $table->date('last_audit_date')->nullable();
            
            // Indépendance (vérification)
            $table->json('independence_check')->nullable(); // {conflicts: [], verified_by, date}
            
            // Commentaires
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('action_plan')->nullable(); // Plan formation/amélioration
            
            // Validation
            $table->enum('status', ['draft', 'validated', 'expired'])->default('draft');
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->date('validity_date')->nullable(); // Date validité (ex: +3 ans)
            
            // Audit trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditor_evaluations');
    }
};
