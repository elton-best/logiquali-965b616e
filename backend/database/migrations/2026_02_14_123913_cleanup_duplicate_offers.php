<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Supprimer l'ancienne offre ISO 9001 (prix 32000)
        $oldOfferId = DB::table('offers')
            ->where('name', 'ISO 9001')
            ->where('price', 32000)
            ->value('id');
            
        if ($oldOfferId) {
            // Supprimer les liens norm_offer
            DB::table('norm_offer')->where('offer_id', $oldOfferId)->delete();
            
            // Supprimer l'offre
            DB::table('offers')->where('id', $oldOfferId)->delete();
            
            echo "✅ Ancienne offre ISO 9001 (32000 FCFA) supprimée\n";
        }
        
        // 2. Uniformiser toutes les offres à 1 mois de durée
        DB::table('offers')->update([
            'duration_months' => 1,
            'updated_at' => now(),
        ]);
        
        echo "✅ Toutes les offres ont maintenant une durée de 1 mois\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback nécessaire (nettoyage irréversible)
    }
};
