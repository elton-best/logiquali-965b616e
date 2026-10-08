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
        Schema::create('norm_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_version_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('norm_sections')->onDelete('cascade');
            
            // Materialized path pour performance (ex: "4.1.2.a")
            $table->string('path')->index();
            
            // Niveau de profondeur (1=chapter, 2=subchapter, etc.)
            $table->integer('level')->default(1);
            
            // Type de section
            $table->enum('type', ['chapter', 'subchapter', 'paragraph', 'point', 'note', 'annex'])->default('chapter');
            
            // Numérotation (ex: "4", "4.1", "4.1.2", "a", "Note 1")
            $table->string('number')->nullable();
            
            // Titre de la section
            $table->string('title')->nullable();
            
            // Contenu texte (exigence)
            $table->text('content')->nullable();
            
            // Ordre dans la hiérarchie (pour tri)
            $table->integer('order_index')->default(0);
            
            // Références croisées (JSON array: ["4.1", "5.2"])
            $table->json('references')->nullable();
            
            // Métadonnées additionnelles
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Index composé pour performance
            $table->index(['norm_version_id', 'parent_id']);
            $table->index(['norm_version_id', 'type']);
            $table->index(['norm_version_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('norm_sections');
    }
};
