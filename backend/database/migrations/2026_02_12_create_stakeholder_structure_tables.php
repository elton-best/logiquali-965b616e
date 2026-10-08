<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Table des Besoins et Attentes
        Schema::create('stakeholder_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stakeholder_id')->constrained()->cascadeOnDelete();
            $table->text('description'); // Description du besoin/attente
            $table->integer('priority')->default(1); // Priorité 1-5
            $table->timestamps();
        });

        // 2. Table des Exigences (liées aux besoins)
        Schema::create('stakeholder_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stakeholder_need_id')->constrained()->cascadeOnDelete();
            $table->text('description'); // Description de l'exigence
            $table->enum('type', ['legal', 'regulatory', 'contractual', 'other'])->default('other');
            $table->string('reference')->nullable(); // Référence légale/réglementaire
            $table->timestamps();
        });

        // 3. Table des Actions de réponse (liées aux exigences)
        Schema::create('stakeholder_requirement_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stakeholder_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // Titre de l'action
            $table->text('description')->nullable(); // Description détaillée
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        // Ajouter index pour performance
        Schema::table('stakeholder_needs', function (Blueprint $table) {
            $table->index('stakeholder_id');
        });

        Schema::table('stakeholder_requirements', function (Blueprint $table) {
            $table->index('stakeholder_need_id');
        });

        Schema::table('stakeholder_requirement_actions', function (Blueprint $table) {
            $table->index('stakeholder_requirement_id');
            $table->index('responsible_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stakeholder_requirement_actions');
        Schema::dropIfExists('stakeholder_requirements');
        Schema::dropIfExists('stakeholder_needs');
    }
};
