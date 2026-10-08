<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue EPI
        Schema::create('epi_catalogue', function (Blueprint $table) {
            $table->id();
            $table->enum('categorie', ['tete', 'yeux_visage', 'ouie', 'voies_respiratoires', 'mains_bras', 'pieds_jambes', 'corps', 'chute_hauteur']);
            $table->string('designation');
            $table->string('reference_fabricant')->nullable();
            $table->string('norme_ce')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Stock EPI par site
        Schema::create('epi_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('epi_catalogue_id')->constrained('epi_catalogue')->onDelete('restrict');
            $table->string('taille')->nullable();
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alerte')->default(10);
            $table->decimal('prix_unitaire', 10, 2)->nullable();
            $table->string('emplacement_stockage')->nullable();
            $table->timestamps();
            $table->index(['enterprise_id', 'site_id']);
        });

        // Mouvements EPI (entrées/sorties)
        Schema::create('epi_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_stock_id')->constrained('epi_stocks')->onDelete('cascade');
            $table->enum('type', ['entree', 'sortie', 'inventaire', 'rebut']);
            $table->integer('quantite');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('motif')->nullable();
            $table->text('observations')->nullable();
            $table->timestamp('date_mouvement');
            $table->timestamps();
        });

        // Attributions EPI aux utilisateurs
        Schema::create('epi_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_stock_id')->constrained('epi_stocks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('quantite_attribuee');
            $table->date('date_attribution');
            $table->date('date_renouvellement_prevue')->nullable();
            $table->enum('statut', ['en_cours', 'restitue', 'perdu', 'detruit'])->default('en_cours');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epi_attributions');
        Schema::dropIfExists('epi_mouvements');
        Schema::dropIfExists('epi_stocks');
        Schema::dropIfExists('epi_catalogue');
    }
};
