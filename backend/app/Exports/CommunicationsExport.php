<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommunicationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with(['site']);
    }

    public function headings(): array
    {
        return [
            'N°',
            'Type',
            'Désignation',
            'Cibles',
            'Moyens',
            'Responsable',
            'Coût (€)',
            'Date Début',
            'Date Fin',
            'Fréquence',
            'Statut',
            'Site',
            'Observations'
        ];
    }

    public function map($communication): array
    {
        return [
            $communication->numero,
            ucfirst($communication->type),
            $communication->designation,
            is_array($communication->cibles) ? implode(', ', $communication->cibles) : $communication->cibles,
            is_array($communication->moyens) ? implode(', ', $communication->moyens) : $communication->moyens,
            $communication->responsable,
            $communication->cout ? number_format($communication->cout, 2) : '',
            $communication->date_debut?->format('d/m/Y'),
            $communication->date_fin?->format('d/m/Y'),
            ucfirst(str_replace('_', ' ', $communication->frequency)),
            ucfirst($communication->status),
            $communication->site?->nom ?? '',
            $communication->observations
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}