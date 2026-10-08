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
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique()->nullable();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            
            // Informations du critère
            $table->string('name');
            $table->string('code')->nullable(); // Code court (ex: C1, C2...)
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // Catégorie de regroupement
            
            // Configuration de notation
            $table->enum('scale_type', ['numeric', 'stars', 'percentage', 'custom'])->default('numeric');
            $table->integer('scale_min')->default(0);
            $table->integer('scale_max')->default(4);
            $table->json('scale_labels')->nullable(); // Labels pour chaque valeur (ex: {"0": "Non applicable", "1": "Insuffisant"...})
            
            // Pondération et calcul
            $table->decimal('weight', 5, 2)->default(1.00); // Coefficient de pondération
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            
            // Association aux types de formulaires
            $table->enum('form_type', [
                'satisfaction_client',
                'evaluation_personnel',
                'evaluation_auditeur',
                'evaluation_fournisseur',
                'audit_interne',
                'custom'
            ])->default('custom');
            
            // Ordre d'affichage
            $table->integer('display_order')->default(0);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['enterprise_id', 'form_type', 'is_active']);
            $table->index(['enterprise_id', 'category']);
        });

        // Table pivot pour lier les critères aux formulaires spécifiques
        Schema::create('evaluation_criteria_form', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_criteria_id')->constrained('evaluation_criteria')->onDelete('cascade');
            $table->morphs('form'); // Polymorphic relation (satisfaction_forms, audits, etc.)
            $table->integer('score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->unique(['evaluation_criteria_id', 'form_type', 'form_id'], 'unique_criteria_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria_form');
        Schema::dropIfExists('evaluation_criteria');
    }
};
