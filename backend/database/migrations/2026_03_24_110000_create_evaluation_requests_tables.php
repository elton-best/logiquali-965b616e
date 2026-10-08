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
        // Table des demandes d'évaluation
        Schema::create('evaluation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique()->nullable();
            $table->uuid('token')->unique(); // Lien unique pour accès externe
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            
            // Type de demande
            $table->enum('type', [
                'satisfaction_client',
                'evaluation_fournisseur',
                'audit_interne',
                'custom'
            ])->default('satisfaction_client');
            
            // Informations sur le destinataire
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_company')->nullable();
            
            // Sujet et contenu
            $table->string('subject');
            $table->text('message')->nullable();
            
            // Dates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Statut
            $table->enum('status', [
                'draft',       // Brouillon
                'pending',     // En attente d'envoi
                'sent',        // Envoyé
                'opened',      // Ouvert (lien cliqué)
                'completed',   // Réponse reçue
                'expired',     // Expiré
                'cancelled'    // Annulé
            ])->default('draft');
            
            // Relances
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            
            // Référence vers l'entité liée (audit, etc.)
            $table->nullableMorphs('requestable'); // audit_id, etc.
            
            // Métadonnées
            $table->json('metadata')->nullable(); // Données additionnelles
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['enterprise_id', 'status']);
            $table->index(['token']);
            $table->index(['recipient_email']);
        });

        // Table des réponses aux évaluations
        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique()->nullable();
            $table->foreignId('evaluation_request_id')->constrained()->onDelete('cascade');
            
            // Réponses aux critères (JSON: {criteria_id: {score, comment}})
            $table->json('responses');
            
            // Score global calculé
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('weighted_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            
            // Commentaire général
            $table->text('general_comment')->nullable();
            
            // Recommandations
            $table->text('recommendations')->nullable();
            
            // Informations IP/navigateur pour traçabilité
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['evaluation_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_requests');
    }
};
