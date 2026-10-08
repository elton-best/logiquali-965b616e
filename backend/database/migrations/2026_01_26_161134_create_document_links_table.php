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
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('documents')->cascadeOnDelete();
            $table->enum('link_type', [
                'parent_child',    // Relation hiérarchique classique
                'reference',       // Référence croisée
                'supersedes',      // Remplace (ancien document)
                'related'          // Document connexe
            ])->default('parent_child');
            $table->text('description')->nullable(); // Précision sur le lien
            $table->timestamps();
            
            $table->unique(['parent_id', 'child_id', 'link_type'], 'unique_document_link');
            $table->index('link_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
