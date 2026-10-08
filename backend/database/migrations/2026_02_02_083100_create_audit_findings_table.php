<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Constats d'audits détaillés avec classification
     * Génère automatiquement des NC si nécessaire
     */
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique(); // CONST-AUD-001-2026
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('audit_checklist_items')->nullOnDelete();
            
            // Classification du constat
            $table->enum('type', [
                'nc_major',        // Non-conformité majeure (systémique, impact critique)
                'nc_minor',        // Non-conformité mineure (ponctuelle)
                'observation',     // Observation (risque potentiel)
                'opportunity',     // Opportunité d'amélioration
                'good_practice'    // Bonne pratique à partager
            ]);
            
            // Axe QHSE concerné
            $table->json('qhse_axes')->nullable(); // ['quality', 'health', 'safety', 'environment']
            
            // Périmètre du constat
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable(); // Zone/Atelier/Bureau
            $table->string('clause_iso')->nullable(); // Ex: "9.1.1", "6.1.2"
            
            // Description détaillée
            $table->string('title');
            $table->text('description'); // Fait observé
            $table->text('evidence')->nullable(); // Preuve objective
            $table->text('requirement')->nullable(); // Exigence non respectée
            
            // Photos/Documents
            $table->json('attachments')->nullable(); // [{url, name, type}]
            
            // Gravité et priorité
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->integer('priority')->default(3); // 1=urgent, 5=faible
            
            // Personnes impliquées
            $table->foreignId('detected_by')->constrained('users')->nullOnDelete(); // Auditeur
            $table->foreignId('concerned_user_id')->nullable()->constrained('users')->nullOnDelete(); // Audité
            $table->timestamp('detected_at');
            
            // Analyse de cause (saisie terrain ou post-audit)
            $table->text('root_cause')->nullable();
            $table->text('immediate_action')->nullable(); // Action immédiate prise
            
            // Lien vers NC créée automatiquement
            $table->foreignId('non_conformity_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('nc_created')->default(false);
            
            // Statut traitement
            $table->enum('status', [
                'open',            // Constaté, en attente traitement
                'nc_created',      // NC créée
                'action_planned',  // Action planifiée
                'resolved',        // Résolu
                'verified',        // Efficacité vérifiée
                'closed'           // Clôturé
            ])->default('open');
            
            $table->date('resolution_deadline')->nullable();
            $table->date('resolved_at')->nullable();
            
            // Audit trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['audit_id', 'type']);
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
