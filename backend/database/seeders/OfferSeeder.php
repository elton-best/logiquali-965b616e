<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('OfferSeeder est obsolète. Exécution du seeder unifié AllISOStandardsSeeder.');
        $this->call(AllISOStandardsSeeder::class);
    }
}
