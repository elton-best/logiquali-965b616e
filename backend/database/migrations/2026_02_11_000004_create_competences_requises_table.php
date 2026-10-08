<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competences_requises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_description_id')->constrained()->onDelete('cascade');
            $table->string('competence_type'); // technique, securite, qualite, reglementaire
            $table->string('competence_name');
            $table->text('description')->nullable();
            $table->enum('level_required', ['base', 'intermediaire', 'avance', 'expert'])->default('base');
            $table->enum('priority', ['obligatoire', 'recommandee', 'optionnelle'])->default('obligatoire');
            $table->integer('validity_months')->nullable(); // Durée de validité
            $table->boolean('requires_certification')->default(false);
            $table->string('certification_authority')->nullable();
            $table->timestamps();

            $table->index(['job_description_id', 'competence_type']);
            $table->index(['enterprise_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competences_requises');
    }
};