<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FixOfferDurationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'FixOfferDurationsSeeder est obsolète et désactivé. '
            . 'Utilisez SuperAdmin pour gérer les durées, ou DatabaseSeeder pour un jeu de données propre.'
        );
    }
}
