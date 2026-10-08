<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinkCommonModulesToNormsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('🔗 Liaison des modules communs aux normes ISO...');

        // Récupérer toutes les normes
        $norms = DB::table('norms')->whereIn('code', [
            'ISO 9001:2015',
            'ISO 14001:2015',
            'ISO 45001:2018',
            'ISO 27001:2022',
            'ISO 22000:2018',
            'ISO 50001:2018',
        ])->get();

        // Récupérer les modules communs (tous sauf "réalisation" qui est spécifique ISO 9001)
        $commonModules = DB::table('modules')->whereIn('code', [
            'contexte',
            'leadership',
            'planification',
            'support',
            'evaluation',
            'amelioration',
        ])->get();

        // Module Réalisation (spécifique ISO 9001 uniquement)
        $realisationModule = DB::table('modules')->where('code', 'realisation')->first();
        $iso9001 = DB::table('norms')->where('code', 'ISO 9001:2015')->first();

        $linksCreated = 0;

        // Lier modules communs à toutes les normes
        foreach ($norms as $norm) {
            foreach ($commonModules as $module) {
                // Vérifier si la liaison existe déjà
                $exists = DB::table('norm_module')
                    ->where('norm_id', $norm->id)
                    ->where('module_id', $module->id)
                    ->exists();

                if (!$exists) {
                    DB::table('norm_module')->insert([
                        'norm_id' => $norm->id,
                        'module_id' => $module->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $linksCreated++;
                }
            }
        }

        // Lier module Réalisation uniquement à ISO 9001
        if ($iso9001 && $realisationModule) {
            $exists = DB::table('norm_module')
                ->where('norm_id', $iso9001->id)
                ->where('module_id', $realisationModule->id)
                ->exists();

            if (!$exists) {
                DB::table('norm_module')->insert([
                    'norm_id' => $iso9001->id,
                    'module_id' => $realisationModule->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $linksCreated++;
            }
        }

        $this->command?->info("✅ {$linksCreated} liaisons créées");
        $this->command?->info("   📦 " . $norms->count() . " normes");
        $this->command?->info("   📂 " . $commonModules->count() . " modules communs");
        $this->command?->info("   🎯 1 module spécifique (Réalisation → ISO 9001)");
    }
}
