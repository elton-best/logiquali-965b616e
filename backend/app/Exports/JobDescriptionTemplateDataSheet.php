<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobDescriptionTemplateDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * En-têtes du template (15 colonnes principales).
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'job_title',
            'mission',
            'user_id',
            'department',
            'reports_to_id',
            'replacement_job_title',
            'activities',
            'main_activities',
            'secondary_activities',
            'work_location',
            'work_schedule',
            'travel_required',
            'required_skills',
            'required_experience',
            'required_education',
        ];
    }

    /**
     * Données du template (1 ligne exemple + 999 lignes vides).
     *
     * @return array
     */
    public function array(): array
    {
        $rows = [];
        
        // Ligne exemple
        $rows[] = [
            'Développeur Full-Stack',
            'Concevoir et développer des applications web innovantes en respectant les bonnes pratiques de développement',
            '',
            'IT',
            '',
            'Développeur Senior',
            'Développement backend et frontend, revue de code, documentation technique',
            'Développement;Tests unitaires;Code review',
            'Support technique;Formation',
            'Paris',
            '9h-18h',
            'Non',
            'PHP;Laravel;Vue.js;PostgreSQL',
            '3 ans minimum en développement web',
            'Bac+5 Informatique',
        ];
        
        // 999 lignes vides pour remplissage
        for ($i = 0; $i < 999; $i++) {
            $rows[] = array_fill(0, 15, '');
        }
        
        return $rows;
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
            // Ligne d'en-tête en gras avec fond gris
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
            // Ligne exemple avec fond vert clair
            2 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9'],
                ],
            ],
        ];
    }

    /**
     * Titre de la feuille.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Données';
    }
}
