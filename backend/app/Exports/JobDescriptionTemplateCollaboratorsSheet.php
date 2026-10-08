<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobDescriptionTemplateCollaboratorsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $enterpriseId
    ) {
    }

    /**
     * En-têtes de la feuille collaborateurs.
     *
     * @return array
     */
    public function headings(): array
    {
        return ['ID', 'Nom', 'Prénom', 'Email'];
    }

    /**
     * Liste des collaborateurs de l'entreprise.
     *
     * @return array
     */
    public function array(): array
    {
        $users = User::query()
            ->where('enterprise_id', $this->enterpriseId)
            ->select('id', 'last_name', 'first_name', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $users->map(fn ($user) => [
            $user->id,
            $user->last_name,
            $user->first_name,
            $user->email,
        ])->toArray();
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
            // En-tête en gras avec fond bleu clair
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BBDEFB'],
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
        return 'Collaborateurs';
    }
}
