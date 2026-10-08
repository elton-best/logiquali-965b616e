<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->enum('criticite_securite', ['faible', 'moyenne', 'elevee', 'critique'])->default('faible')->after('actif');
            $table->boolean('necessite_vgp')->default(false)->after('criticite_securite');
            $table->integer('periodicite_vgp_mois')->nullable()->after('necessite_vgp');
            $table->json('epi_requis')->nullable()->after('periodicite_vgp_mois');
            $table->json('habilitations_requises')->nullable()->after('epi_requis');
            $table->text('consignes_securite')->nullable()->after('habilitations_requises');
        });
    }

    public function down(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->dropColumn([
                'criticite_securite',
                'necessite_vgp',
                'periodicite_vgp_mois',
                'epi_requis',
                'habilitations_requises',
                'consignes_securite'
            ]);
        });
    }
};
