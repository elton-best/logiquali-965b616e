<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            
            // Type de revue
            $table->enum('type', ['periodique', 'exceptionnelle', 'audit', 'amelioration'])
                ->default('periodique');
            $table->date('review_date');
            $table->string('version_reviewed', 20)->comment('Version du processus revue');
            
            // Participants
            $table->foreignId('led_by')->constrained('users')->comment('Responsable de la revue');
            $table->json('participants')->nullable()->comment('Liste des participants');
            
            // Résultats de la revue
            $table->text('objectives')->nullable()->comment('Objectifs de la revue');
            $table->text('findings')->nullable()->comment('Constats');
            $table->text('strengths')->nullable()->comment('Points forts');
            $table->text('weaknesses')->nullable()->comment('Points faibles');
            $table->text('opportunities_improvement')->nullable()->comment('Opportunités d\'amélioration');
            
            // Décision
            $table->enum('decision', ['approved', 'approved_with_changes', 'rejected', 'revision_required'])
                ->default('approved');
            $table->text('decision_comment')->nullable();
            $table->json('action_items')->nullable()->comment('Actions à mener');
            
            // Suivi
            $table->date('next_review_date')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])
                ->default('planned');
            
            // Documents joints
            $table->json('attachments')->nullable()->comment('PV, compte-rendu, etc.');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_reviews');
    }
};
