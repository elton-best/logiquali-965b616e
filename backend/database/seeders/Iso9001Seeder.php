<?php

namespace Database\Seeders;

use App\Models\Norm;
use App\Models\NormSection;
use App\Models\NormVersion;
use Illuminate\Database\Seeder;

class Iso9001Seeder extends Seeder
{
    public function run(): void
    {
        $norm = Norm::firstOrCreate(
            ['code' => 'ISO9001'],
            [
                'name' => 'ISO 9001',
                'description' => 'Système de management de la qualité',
                'domain' => 'quality',
                'status' => 'published',
            ]
        );

        $version = NormVersion::firstOrCreate(
            ['norm_id' => $norm->id, 'version_code' => '2015'],
            [
                'full_code' => 'ISO 9001:2015',
                'published_at' => now()->toDateString(),
                'is_current' => true,
            ]
        );

        $norm->update(['current_version_id' => $version->id]);

        $chapters = [
            '4' => [
                'title' => "Contexte de l'organisation",
                'children' => [
                    '4.1' => "Compréhension de l'organisation et de son contexte",
                    '4.2' => "Compréhension des besoins et attentes des parties intéressées",
                    '4.3' => "Détermination du périmètre du SMQ",
                    '4.4' => "Système de management de la qualité et ses processus",
                ],
            ],
            '5' => [
                'title' => 'Leadership',
                'children' => [
                    '5.1' => 'Leadership et engagement',
                    '5.2' => 'Politique qualité',
                    '5.3' => 'Rôles, responsabilités et autorités',
                ],
            ],
            '6' => [
                'title' => 'Planification',
                'children' => [
                    '6.1' => 'Actions à mettre en œuvre face aux risques et opportunités',
                    '6.2' => 'Objectifs qualité et planification pour les atteindre',
                    '6.3' => 'Planification des changements',
                ],
            ],
            '7' => [
                'title' => 'Support',
                'children' => [
                    '7.1' => 'Ressources',
                    '7.2' => 'Compétence',
                    '7.3' => 'Sensibilisation',
                    '7.4' => 'Communication',
                    '7.5' => 'Informations documentées',
                ],
            ],
            '8' => [
                'title' => 'Réalisation des activités opérationnelles',
                'children' => [
                    '8.1' => 'Planification et maîtrise opérationnelles',
                    '8.2' => 'Exigences relatives aux produits et services',
                    '8.3' => 'Conception et développement',
                    '8.4' => 'Maîtrise des processus, produits et services fournis par des prestataires externes',
                    '8.5' => 'Production et prestation de service',
                    '8.6' => 'Libération des produits et services',
                    '8.7' => 'Maîtrise des éléments de sortie non conformes',
                ],
            ],
            '9' => [
                'title' => 'Évaluation des performances',
                'children' => [
                    '9.1' => 'Surveillance, mesure, analyse et évaluation',
                    '9.2' => 'Audit interne',
                    '9.3' => 'Revue de direction',
                ],
            ],
            '10' => [
                'title' => 'Amélioration',
                'children' => [
                    '10.1' => 'Généralités',
                    '10.2' => 'Non-conformité et action corrective',
                    '10.3' => 'Amélioration continue',
                ],
            ],
        ];

        $order = 1;
        foreach ($chapters as $chapterNumber => $chapter) {
            $chapterSection = NormSection::firstOrCreate(
                [
                    'norm_version_id' => $version->id,
                    'number' => $chapterNumber,
                    'type' => 'chapter',
                ],
                [
                    'title' => $chapter['title'],
                    'level' => 1,
                    'order_index' => $order++,
                    'path' => $chapterNumber,
                ]
            );

            $childOrder = 1;
            foreach ($chapter['children'] as $childNumber => $childTitle) {
                NormSection::firstOrCreate(
                    [
                        'norm_version_id' => $version->id,
                        'number' => $childNumber,
                        'type' => 'subchapter',
                    ],
                    [
                        'parent_id' => $chapterSection->id,
                        'title' => $childTitle,
                        'level' => 2,
                        'order_index' => $childOrder++,
                        'path' => NormSection::buildPath($chapterSection, $childNumber),
                    ]
                );
            }
        }
    }
}
