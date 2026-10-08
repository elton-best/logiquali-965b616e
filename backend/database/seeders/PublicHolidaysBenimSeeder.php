<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\PublicHoliday;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PublicHolidaysBenimSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer toutes les entreprises avec un site au Bénin
        $enterprises = Enterprise::with('sites')->get();

        foreach ($enterprises as $enterprise) {
            // Créer les jours fériés pour le Bénin
            $holidays = [
                [
                    'country_code' => 'BJ',
                    'date' => '2026-01-01',
                    'name' => 'Jour de l\'An',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-03-08',
                    'name' => 'Jour international des femmes',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-04-17',
                    'name' => 'Lundi de Pâques',
                    'recurring' => false,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-05-01',
                    'name' => 'Fête du Travail',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-05-26',
                    'name' => 'Ascension',
                    'recurring' => false,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-06-05',
                    'name' => 'Lundi de Pentecôte',
                    'recurring' => false,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-08-15',
                    'name' => 'Assomption',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-11-01',
                    'name' => 'Toussaint',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-11-30',
                    'name' => 'Fête de l\'indépendance',
                    'recurring' => true,
                ],
                [
                    'country_code' => 'BJ',
                    'date' => '2026-12-25',
                    'name' => 'Noël',
                    'recurring' => true,
                ],
            ];

            foreach ($holidays as $holiday) {
                PublicHoliday::updateOrCreate(
                    [
                        'enterprise_id' => $enterprise->id,
                        'country_code' => $holiday['country_code'],
                        'date' => $holiday['date'],
                    ],
                    [
                        'name' => $holiday['name'],
                        'recurring' => $holiday['recurring'],
                    ]
                );
            }

            $this->command->info("Jours fériés Bénin créés pour l'entreprise: {$enterprise->name}");
        }
    }
}
