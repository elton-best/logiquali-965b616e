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
        Schema::create('client_satisfaction_forms', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique()->comment('Référence auto-générée');
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('client_name')->comment('Nom de la structure cliente');
            $table->date('survey_date')->comment('Date de la fiche');
            
            // Critères d'évaluation (échelle 1-4)
            $table->tinyInteger('amabilite_ecoute')->comment('Aimabilité et écoute du client (1-4)');
            $table->tinyInteger('disponibilite_spontaneite')->comment('Disponibilité/Spontanéité (1-4)');
            $table->tinyInteger('rapidite_traitement')->comment('Rapidité dans le traitement des requêtes (1-4)');
            $table->tinyInteger('respect_delais')->comment('Respect des délais de livraison (1-4)');
            $table->tinyInteger('conformite_produits')->comment('Conformité des produits livrés (1-4)');
            $table->tinyInteger('traitement_reclamations')->comment('Traitement des réclamations et plaintes (1-4)');
            
            // Scores calculés automatiquement
            $table->tinyInteger('total_score')->comment('Note totale /24');
            $table->decimal('satisfaction_percentage', 5, 2)->comment('Pourcentage de satisfaction');
            
            // Niveau de satisfaction global
            $table->enum('satisfaction_level', [
                'very_satisfied',      // Très satisfait (>= 90%)
                'satisfied',           // Satisfait (70-89%)
                'dissatisfied',        // Insatisfait (50-69%)
                'very_dissatisfied'    // Très insatisfait (< 50%)
            ])->nullable();
            
            // Recommandations
            $table->text('recommendations')->nullable()->comment('Recommandations pour amélioration');
            
            // Métadonnées
            $table->string('status')->default('draft')->comment('draft, submitted, reviewed');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            
            // Index pour performances
            $table->index('survey_date');
            $table->index('satisfaction_level');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_satisfaction_forms');
    }
};
