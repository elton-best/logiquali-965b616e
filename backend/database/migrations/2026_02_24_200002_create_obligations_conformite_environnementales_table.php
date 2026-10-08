<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligations_conformite_environnementales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['reglementaire', 'volontaire', 'contractuelle']);
            $table->string('reference'); // Ex: ICPE, Loi sur l'eau
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('autorite_competente')->nullable(); // DREAL, Préfecture
            $table->date('date_application')->nullable();
            $table->enum('periodicite_controle', ['mensuel', 'trimestriel', 'semestriel', 'annuel', 'ponctuel'])->nullable();
            $table->date('date_prochain_controle')->nullable();
            $table->enum('statut_conformite', ['conforme', 'non_conforme', 'en_cours', 'non_applicable'])->default('en_cours');
            $table->text('preuves_conformite')->nullable();
            $table->string('document_path')->nullable();
            $table->text('actions_correctives')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['enterprise_id', 'site_id']);
            $table->index('date_prochain_controle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations_conformite_environnementales');
    }
};
