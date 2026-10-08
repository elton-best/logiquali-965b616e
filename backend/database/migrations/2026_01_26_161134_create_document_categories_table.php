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
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Politique, Manuel, Procédure, Instruction, Enregistrement
            $table->string('code', 10)->unique(); // POL, MAN, PROC, INST, ENR
            $table->integer('level'); // 1-5 (1 = plus haut niveau)
            $table->text('description')->nullable();
            $table->integer('retention_period_years')->default(5); // Durée conservation par défaut
            $table->json('required_approvers')->nullable(); // Rôles requis pour approbation
            $table->integer('order')->default(0); // Ordre affichage
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_categories');
    }
};
