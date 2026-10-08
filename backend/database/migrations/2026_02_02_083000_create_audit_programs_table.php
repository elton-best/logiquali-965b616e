<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Programme annuel d'audits conforme ISO 9001:2015 §9.2
     * Planification basée sur les risques (§6.1)
     */
    public function up(): void
    {
        Schema::create('audit_programs', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique(); // PRG-AUDIT-2026
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->string('title'); // "Programme Audits Internes 2026"
            
            // Responsable du programme
            $table->foreignId('program_manager_id')->constrained('users')->nullOnDelete();
            
            // Objectifs du programme
            $table->text('objectives')->nullable(); // Objectifs annuels
            $table->text('scope')->nullable(); // Périmètre d'application
            $table->json('target_processes')->nullable(); // [{process_id, frequency, priority}]
            $table->json('target_sites')->nullable(); // [{site_id, planned_audits_count}]
            
            // Priorisation basée risques
            $table->json('risk_based_criteria')->nullable(); // Critères de priorisation
            $table->integer('planned_audits_count')->default(0);
            $table->integer('completed_audits_count')->default(0);
            
            // Statistiques
            $table->integer('conformity_rate')->nullable(); // Taux de conformité global %
            $table->integer('avg_conformity_rate')->nullable(); // Taux de conformité moyen %
            $table->integer('nc_major_count')->default(0);
            $table->integer('nc_minor_count')->default(0);
            $table->integer('observations_count')->default(0);
            
            // Revue périodique
            $table->date('last_review_date')->nullable();
            $table->date('next_review_date')->nullable();
            $table->text('notes')->nullable(); // Notes générales
            
            // Workflow
            $table->enum('status', [
                'draft',          // Brouillon
                'validated',      // Validé par direction
                'in_progress',    // En cours d'exécution
                'completed',      // Année terminée
                'archived'        // Archivé
            ])->default('draft');
            
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            
            // Audit trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['site_id', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_programs');
    }
};
