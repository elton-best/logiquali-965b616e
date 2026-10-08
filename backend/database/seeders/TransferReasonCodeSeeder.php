<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\TransferReasonCode;
use Illuminate\Database\Seeder;

class TransferReasonCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all enterprises to seed reason codes for each
        $enterprises = Enterprise::all();

        // Default transfer reason codes for all enterprises
        $reasons = [
            [
                'code' => 'maintenance',
                'name' => 'Maintenance',
                'description' => 'Transfert pour maintenance préventive ou corrective',
            ],
            [
                'code' => 'repair',
                'name' => 'Réparation',
                'description' => 'Transfert pour réparation de l\'équipement',
            ],
            [
                'code' => 'consolidation',
                'name' => 'Consolidation',
                'description' => 'Consolidation d\'équipements ou regroupement',
            ],
            [
                'code' => 'transfer_site',
                'name' => 'Transfert de site',
                'description' => 'Transfert d\'un site à un autre',
            ],
            [
                'code' => 'acquisition',
                'name' => 'Acquisition',
                'description' => 'Nouvel équipement acquis',
            ],
            [
                'code' => 'relocation',
                'name' => 'Relocalisation',
                'description' => 'Relocalisation interne ou déménagement',
            ],
            [
                'code' => 'obsolescence',
                'name' => 'Obsolescence',
                'description' => 'Transfert lié à l\'obsolescence ou mise au rebut',
            ],
            [
                'code' => 'other',
                'name' => 'Autre',
                'description' => 'Autre raison de transfert',
            ],
        ];

        // Seed for each enterprise
        foreach ($enterprises as $enterprise) {
            foreach ($reasons as $reason) {
                TransferReasonCode::firstOrCreate(
                    [
                        'enterprise_id' => $enterprise->id,
                        'code' => $reason['code'],
                    ],
                    [
                        'name' => $reason['name'],
                        'description' => $reason['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
