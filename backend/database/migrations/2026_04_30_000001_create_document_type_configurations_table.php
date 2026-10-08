<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained('sites')->onDelete('cascade');
            $table->string('name', 100); // ex: "Politique", "Procédure"
            $table->string('abbreviation', 10); // ex: "POL", "PRC", "PRD"
            $table->tinyInteger('abbreviation_length')->default(3); // 3, 4, ou 5 caractères
            $table->enum('scope', ['enterprise', 'site'])->default('site');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour performance
            $table->index(['enterprise_id', 'is_active']);
            $table->index(['site_id', 'is_active']);
            $table->index('abbreviation');

            // Contrainte unique : une abréviation par entreprise
            $table->unique(['enterprise_id', 'abbreviation'], 'unique_abbreviation_per_enterprise');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_configurations');
    }
};
