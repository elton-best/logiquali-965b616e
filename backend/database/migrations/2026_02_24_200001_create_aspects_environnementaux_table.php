<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aspects_environnementaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('equipement_id')->nullable()->constrained('equipements')->onDelete('set null');
            $table->foreignId('process_id')->nullable()->constrained('processes')->onDelete('set null');
            $table->enum('type', ['emission_air', 'rejet_eau', 'dechet', 'bruit', 'odeur', 'consommation_ressource', 'pollution_sol', 'autre']);
            $table->string('designation');
            $table->text('description')->nullable();
            $table->enum('condition', ['normale', 'anormale', 'urgence']);
            $table->integer('gravite')->default(1); // 1-5
            $table->integer('frequence')->default(1); // 1-5
            $table->integer('detectabilite')->default(1); // 1-5
            $table->integer('criticite')->storedAs('gravite * frequence * detectabilite');
            $table->boolean('aspect_significatif')->default(false);
            $table->text('mesures_maitrise')->nullable();
            $table->text('objectifs_amelioration')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['enterprise_id', 'site_id']);
            $table->index('aspect_significatif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspects_environnementaux');
    }
};
