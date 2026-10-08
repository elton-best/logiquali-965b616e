<?php

namespace App\Services\Docx;

use App\Models\Enterprise;
use Illuminate\Support\Collection;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class StakeholderDocxGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    public function generate(Collection $stakeholders, string $siteName, ?Enterprise $enterprise = null): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // Titre
        $section->addText(
            'REGISTRE DES PARTIES INTÉRESSÉES',
            ['bold' => true, 'size' => 16, 'color' => '2E74B5'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
        );

        $section->addText(
            $siteName,
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 480]
        );

        // Date
        $section->addText(
            'Date: ' . now()->format('d/m/Y'),
            ['size' => 10, 'italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 240]
        );

        // Introduction
        $section->addText(
            'Ce registre identifie les parties intéressées pertinentes pour le système de management de la qualité et leurs besoins et attentes.',
            ['size' => 11],
            ['spaceAfter' => 480, 'alignment' => Jc::BOTH]
        );

        // Tableau des parties intéressées
        if ($stakeholders->count() > 0) {
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'cellMargin' => 80
            ]);

            // En-tête
            $table->addRow(500);
            $table->addCell(2500)->addText('Besoins et Attentes', ['bold' => true, 'size' => 10]);
            $table->addCell(2500)->addText('Exigences', ['bold' => true, 'size' => 10]);
            $table->addCell(2500)->addText('Actions', ['bold' => true, 'size' => 10]);
            $table->addCell(1500)->addText('Responsable', ['bold' => true, 'size' => 10]);
            $table->addCell(1200)->addText('Délai', ['bold' => true, 'size' => 10]);

            // Données - Structure plate
            foreach ($stakeholders as $stakeholder) {
                if (!empty($stakeholder->needs)) {
                    foreach ($stakeholder->needs as $need) {
                        if (!empty($need['requirements'])) {
                            foreach ($need['requirements'] as $req) {
                                if (!empty($req['actions'])) {
                                    foreach ($req['actions'] as $action) {
                                        $table->addRow();
                                        $table->addCell(2500)->addText($need['description'] ?? '', ['size' => 10]);
                                        $table->addCell(2500)->addText($req['description'] ?? '', ['size' => 10]);
                                        $table->addCell(2500)->addText($action['description'] ?? '', ['size' => 10]);
                                        $table->addCell(1500)->addText($action['responsible'] ?? '', ['size' => 10]);
                                        $table->addCell(1200)->addText(
                                            !empty($action['deadline']) ? \Carbon\Carbon::parse($action['deadline'])->format('d/m/Y') : '',
                                            ['size' => 10]
                                        );
                                    }
                                } else {
                                    $table->addRow();
                                    $table->addCell(2500)->addText($need['description'] ?? '', ['size' => 10]);
                                    $table->addCell(2500)->addText($req['description'] ?? '', ['size' => 10]);
                                    $table->addCell(2500)->addText('', ['size' => 10]);
                                    $table->addCell(1500)->addText('', ['size' => 10]);
                                    $table->addCell(1200)->addText('', ['size' => 10]);
                                }
                            }
                        } else {
                            $table->addRow();
                            $table->addCell(2500)->addText($need['description'] ?? '', ['size' => 10]);
                            $table->addCell(2500)->addText('', ['size' => 10]);
                            $table->addCell(2500)->addText('', ['size' => 10]);
                            $table->addCell(1500)->addText('', ['size' => 10]);
                            $table->addCell(1200)->addText('', ['size' => 10]);
                        }
                    }
                }
            }
        } else {
            $section->addText(
                'Aucune partie intéressée enregistrée.',
                ['italic' => true, 'size' => 11],
                ['alignment' => Jc::CENTER]
            );
        }

        // Générer le fichier
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $outputPath = storage_path('app/temp/Registre_Parties_Interessees_' . time() . '.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return $outputPath;
    }

    private function getTypeLabel(string $type): string
    {
        $labels = [
            'clientb' => 'Client',
            'supplier' => 'Fournisseur',
            'partner' => 'Partenaire',
            'regulator' => 'Régulateur',
            'employee' => 'Employé',
            'shareholder' => 'Actionnaire',
            'other' => 'Autre'
        ];
        return $labels[$type] ?? $type;
    }

    private function getRelevanceLabel(?string $degree): string
    {
        $labels = [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Élevée'
        ];
        return $labels[$degree] ?? $degree ?? 'Non défini';
    }
}
