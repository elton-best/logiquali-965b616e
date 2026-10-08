<?php

use Database\Seeders\CommonQHSECatalogSeeder;
use Database\Seeders\LinkCommonModulesToNormsSeeder;
use Database\Seeders\NormativeCatalogMatrixSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sub_module_sections')
            ->where('code', 'fiche_poste')
            ->update([
                'route' => '/company/leadership/fiche_poste',
                'updated_at' => now(),
            ]);

        DB::table('sub_module_sections')
            ->where('code', 'fiche_responsabilite')
            ->update([
                'route' => '/company/leadership/fiche_responsabilite',
                'updated_at' => now(),
            ]);

        // Data backfill for existing environments where only partial catalog was seeded.
        app(CommonQHSECatalogSeeder::class)->run();
        app(LinkCommonModulesToNormsSeeder::class)->run();
        app(NormativeCatalogMatrixSeeder::class)->run();
    }

    public function down(): void
    {
        // Intentionally no-op: this migration normalizes reference catalog data.
    }
};
