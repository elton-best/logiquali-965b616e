<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->boolean('usage_energetique_significatif')->default(false)->after('dechets_generes');
            $table->enum('type_energie_principale', ['electricite', 'gaz', 'fuel', 'vapeur', 'air_comprime', 'autre'])->nullable()->after('usage_energetique_significatif');
            $table->decimal('puissance_nominale_kw', 10, 2)->nullable()->after('type_energie_principale');
            $table->integer('heures_fonctionnement_an')->nullable()->after('puissance_nominale_kw');
            $table->decimal('consommation_estimee_kwh_an', 15, 2)->nullable()->after('heures_fonctionnement_an');
            $table->enum('classe_energetique', ['A+++', 'A++', 'A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G'])->nullable()->after('consommation_estimee_kwh_an');
        });
    }

    public function down(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->dropColumn([
                'usage_energetique_significatif',
                'type_energie_principale',
                'puissance_nominale_kw',
                'heures_fonctionnement_an',
                'consommation_estimee_kwh_an',
                'classe_energetique'
            ]);
        });
    }
};
