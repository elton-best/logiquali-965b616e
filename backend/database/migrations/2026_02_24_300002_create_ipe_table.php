<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('formule_calcul');
            $table->string('unite');
            $table->decimal('valeur_reference', 15, 3)->nullable();
            $table->decimal('objectif_cible', 15, 3)->nullable();
            $table->date('date_reference')->nullable();
            $table->enum('periodicite', ['journalier', 'hebdomadaire', 'mensuel', 'trimestriel', 'annuel']);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['enterprise_id', 'site_id']);
        });

        Schema::create('ipe_valeurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipe_id')->constrained('ipe')->onDelete('cascade');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->decimal('valeur', 15, 3);
            $table->decimal('ecart_reference', 15, 3)->nullable();
            $table->decimal('ecart_objectif', 15, 3)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
            $table->index('ipe_id');
            $table->index(['periode_debut', 'periode_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipe_valeurs');
        Schema::dropIfExists('ipe');
    }
};
