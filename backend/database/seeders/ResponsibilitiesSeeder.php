<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResponsibilitiesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('ResponsibilitiesSeeder - Les responsabilités sont créées manuellement par les utilisateurs.');
        $this->command->info('Aucune donnée pré-remplie pour les responsabilités.');
    }
}

