<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobDescriptionTemplateInstructionsSheet implements FromArray, WithStyles, WithTitle
{
    /**
     * Instructions détaillées pour remplir le template.
     *
     * @return array
     */
    public function array(): array
    {
        return [
            ['📋 GUIDE D\'IMPORT - FICHES DE POSTE'],
            [''],
            ['CHAMPS OBLIGATOIRES (*)'],
            ['• job_title* : Intitulé du poste (max 255 caractères)'],
            ['• mission* : Mission principale du poste'],
            [''],
            ['CHAMPS OPTIONNELS'],
            ['• user_id : ID du collaborateur (voir feuille "Collaborateurs")'],
            ['• department : Département (ex: IT, RH, Finance, Commercial)'],
            ['• reports_to_id : ID du manager (voir feuille "Collaborateurs")'],
            ['• replacement_job_title : Intitulé du poste de remplacement'],
            ['• activities : Description générale des activités'],
            ['• main_activities : Activités principales (séparées par point-virgule)'],
            ['• secondary_activities : Activités secondaires (séparées par point-virgule)'],
            ['• work_location : Lieu de travail (ex: Paris, Télétravail)'],
            ['• work_schedule : Horaire de travail (ex: 9h-18h, Temps partiel)'],
            ['• travel_required : Déplacements requis (Oui/Non ou 1/0)'],
            ['• required_skills : Compétences requises (séparées par point-virgule)'],
            ['• required_experience : Expérience requise (ex: 3 ans minimum)'],
            ['• required_education : Formation requise (ex: Bac+5 Informatique)'],
            [''],
            ['FORMAT DES LISTES'],
            ['Séparer les éléments par point-virgule (;)'],
            ['Exemple : PHP;Laravel;Vue.js'],
            [''],
            ['IMPORTANT'],
            ['• Les colonnes avec * sont obligatoires'],
            ['• Les ID user_id et reports_to_id doivent exister dans votre entreprise'],
            ['• Vérifiez la feuille "Collaborateurs" pour trouver les IDs'],
            ['• Ne modifiez pas les en-têtes de la feuille "Données"'],
            [''],
            ['LIMITES'],
            ['• Maximum recommandé : 5000 lignes par fichier'],
            ['• Taille maximale : 50 MB'],
            ['• Formats acceptés : .xlsx, .xls, .csv'],
            [''],
            ['SUPPORT'],
            ['En cas de problème, contactez votre administrateur système.'],
        ];
    }

    /**
     * Styles de la feuille.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Titre en gras et plus grand
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
            ],
            // Sections en gras
            3 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true]],
            22 => ['font' => ['bold' => true]],
            26 => ['font' => ['bold' => true]],
            31 => ['font' => ['bold' => true]],
            36 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Titre de la feuille.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Instructions';
    }
}
