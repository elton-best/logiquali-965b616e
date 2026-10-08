<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consommations_energie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('equipement_id')->nullable()->constrained('equipements')->onDelete('cascade');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->enum('type_energie', ['electricite', 'gaz', 'fuel', 'vapeur', 'air_comprime', 'autre']);
            $table->decimal('valeur_consommation', 15, 3);
            $table->enum('unite', ['kwh', 'm3', 'litres', 'tonnes']);
            $table->enum('mode_saisie', ['releve_reel', 'estimation', 'import_compteur']);
            $table->string('source_donnee')->nullable();
            $table->decimal('cout_euro', 15, 2)->nullable();
            $table->decimal('emission_co2_kg', 15, 2)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->index(['enterprise_id', 'site_id']);
            $table->index(['periode_debut', 'periode_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consommations_energie');
    }
};
