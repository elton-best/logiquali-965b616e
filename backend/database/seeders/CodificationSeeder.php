<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CodificationElement;

class CodificationSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'MOB', 'libelle' => 'Mobilier', 'description' => 'Cette catégorie regroupe tout le matériel destiné à l\'aménagement et au confort des espaces de travail. On distingue le mobilier de bureau (bureaux, chaises, fauteuils), le mobilier de rangement (armoires, étagères, casiers) et le mobilier d\'accueil ou de réunion (tables de réunion, comptoirs d\'accueil).'],
            ['code' => 'INF', 'libelle' => 'Informatique et bureautique', 'description' => 'Cette catégorie comprend l\'ensemble du matériel lié aux technologies de l\'information et aux outils de gestion. Elle inclut l\'informatique fixe (ordinateurs de bureau, serveurs), l\'informatique portable (PC portables, tablettes), les périphériques et systèmes d\'impression (imprimantes, scanners, photocopieurs) ainsi que le matériel de communication (téléphones, vidéoprojecteurs, écrans).'],
            ['code' => 'MEN', 'libelle' => 'Electroménagers', 'description' => 'Elle couvre tous les appareils électoménagers et donc les appareils fonctionnant à l\'électricité et utilisé dans la maison pour améliorer le confort ou faciliter les tâches quotidiennes. (micro onde, chauffe eaux etc…)'],
            ['code' => 'PRO', 'libelle' => 'Protection', 'description' => 'Cette catégorie couvre les équipements destinés à assurer la sécurité des personnes et des biens ou produits, ainsi que la préservation du patrimoine matériel. Elle comprend la sécurité incendie (extincteurs, détecteurs de fumée), la sécurité des biens (coffres-forts, armoires sécurisées).'],
        ];

        $localisations = [
            ['code' => 'MAG', 'libelle' => 'Magasin'],
            ['code' => 'SFO', 'libelle' => 'Salle de formation'],
            ['code' => 'CEO', 'libelle' => 'Bureau du CEO'],
            ['code' => 'COO', 'libelle' => 'Bureau du COO'],
            ['code' => 'CON', 'libelle' => 'Bureau des consultants'],
            ['code' => 'ADM', 'libelle' => 'Bureau du CFO et EA'],
            ['code' => 'DEC', 'libelle' => 'Bureau DEV et Comerciale'],
            ['code' => 'SEC', 'libelle' => 'Sécrétariat'],
            ['code' => 'COU', 'libelle' => 'Couloir'],
            ['code' => 'GUR', 'libelle' => 'Guérite'],
            ['code' => 'CUI', 'libelle' => 'Cuisine'],
        ];

        foreach ($categories as $categorie) {
            CodificationElement::create([
                'type' => 'categorie',
                'code' => $categorie['code'],
                'libelle' => $categorie['libelle'],
                'description' => $categorie['description'] ?? null,
                'actif' => true,
            ]);
        }

        foreach ($localisations as $localisation) {
            CodificationElement::create([
                'type' => 'localisation',
                'code' => $localisation['code'],
                'libelle' => $localisation['libelle'],
                'actif' => true,
            ]);
        }
    }
}
