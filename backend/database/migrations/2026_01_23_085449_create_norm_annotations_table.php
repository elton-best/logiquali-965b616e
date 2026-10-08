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
        Schema::create('norm_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_section_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('enterprise_id')->nullable()->constrained()->onDelete('cascade');
            
            // Contenu de l'annotation
            $table->text('content');
            
            // Visibilité (true = privé pour Client A, false = public)
            $table->boolean('is_private')->default(true);
            
            // Type d'annotation
            $table->enum('type', ['note', 'question', 'clarification', 'implementation'])->default('note');
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['norm_section_id', 'user_id']);
            $table->index(['enterprise_id', 'is_private']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('norm_annotations');
    }
};
