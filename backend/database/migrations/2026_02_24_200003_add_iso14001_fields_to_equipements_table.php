<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->boolean('impact_environnemental')->default(false)->after('consignes_securite');
            $table->enum('type_impact', ['emission', 'rejet', 'dechet', 'bruit', 'consommation'])->nullable()->after('impact_environnemental');
            $table->decimal('consommation_eau_m3_an', 10, 2)->nullable()->after('type_impact');
            $table->decimal('consommation_energie_kwh_an', 10, 2)->nullable()->after('consommation_eau_m3_an');
            $table->decimal('emission_co2_kg_an', 10, 2)->nullable()->after('consommation_energie_kwh_an');
            $table->text('dechets_generes')->nullable()->after('emission_co2_kg_an');
        });
    }

    public function down(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->dropColumn([
                'impact_environnemental',
                'type_impact',
                'consommation_eau_m3_an',
                'consommation_energie_kwh_an',
                'emission_co2_kg_an',
                'dechets_generes'
            ]);
        });
    }
};
