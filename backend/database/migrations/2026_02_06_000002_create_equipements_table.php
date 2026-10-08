<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->string('code_complet')->unique();
            $table->foreignId('categorie_id')->constrained('codification_elements')->onDelete('restrict');
            $table->foreignId('localisation_id')->constrained('codification_elements')->onDelete('restrict');
            $table->string('nom_commun');
            $table->string('nom_commun_abrege', 3);
            $table->string('indice', 3);
            $table->year('annee_acquisition');
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('numero_serie')->nullable();
            $table->enum('etat', ['tres_bon', 'bon', 'mauvais'])->default('bon');
            $table->decimal('valeur_acquisition', 15, 2)->nullable();
            $table->text('observations')->nullable();
            $table->boolean('necessite_maintenance')->default(false);
            $table->integer('frequence_maintenance_jours')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
