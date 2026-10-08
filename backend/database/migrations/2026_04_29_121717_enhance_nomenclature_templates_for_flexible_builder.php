<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomenclature_templates', function (Blueprint $table) {
            // Séparateur utilisé entre les parties du code (ex: "-", "_", ".")
            $table->string('separator', 5)->default('-')->after('format_structure');
            
            // Exemple de code généré pour prévisualisation
            $table->string('preview_example', 100)->nullable()->after('separator');
            
            // Métadonnées additionnelles pour le builder
            $table->json('builder_config')->nullable()->after('preview_example');
        });
    }

    public function down(): void
    {
        Schema::table('nomenclature_templates', function (Blueprint $table) {
            $table->dropColumn(['separator', 'preview_example', 'builder_config']);
        });
    }
};
