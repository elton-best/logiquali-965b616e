<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('SubModuleSeeder obsolète: ignoré (catalogue unifié via AllISOStandardsSeeder + NormativeCatalogMatrixSeeder).');
    }
}
