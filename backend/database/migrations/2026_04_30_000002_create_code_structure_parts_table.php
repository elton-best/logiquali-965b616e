<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_structure_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_configuration_id')
                ->constrained('document_type_configurations')
                ->onDelete('cascade');
            $table->tinyInteger('part_order')->unsigned(); // 1, 2, 3, 4...
            $table->string('part_name', 100); // ex: "Type de document", "Processus"
            $table->enum('part_type', [
                'fixed_abbreviation',    // Abréviation fixe du type (POL, PRC)
                'process_abbreviation',  // Abréviation du processus (RH, COM)
                'sequence',              // Numéro séquentiel (001, 002)
                'year',                  // Année (2026)
                'month',                 // Mois (01-12)
                'day',                   // Jour (01-31)
                'free_text',             // Texte libre saisi par utilisateur
                'site_code',             // Code du site
            ]);
            $table->tinyInteger('part_length')->unsigned()->nullable(); // Nombre de caractères
            $table->string('separator_after', 5)->nullable(); // "_", "-", ".", null
            $table->boolean('is_required')->default(true);
            $table->string('default_value', 50)->nullable(); // Valeur par défaut pour types fixes
            $table->enum('sequence_scope', [
                'global',                    // Séquence globale
                'by_type',                   // Par type de document
                'by_type_process',           // Par type + processus
                'by_type_year',              // Par type + année
                'by_type_process_year',      // Par type + processus + année
                'by_type_process_year_month' // Par type + processus + année + mois
            ])->nullable(); // Uniquement pour part_type = 'sequence'
            $table->timestamps();

            // Index pour performance
            $table->index(['document_type_configuration_id', 'part_order']);

            // Contrainte unique : part_order unique par configuration
            $table->unique(
                ['document_type_configuration_id', 'part_order'],
                'unique_part_order_per_config'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_structure_parts');
    }
};
