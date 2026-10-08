<?php

namespace App\Services\Docx;

use App\Models\Enterprise;
use App\Models\Site;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Table;

class ProcessCartographyDocxGenerator
{
    private PhpWord $phpWord;
    private $section;
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    /**
     * Generate Process Cartography DOCX file
     */
    public function generate(array $cartographyData, ?Enterprise $enterprise = null, array $documentMeta = []): string
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Calibri');
        $this->phpWord->setDefaultFontSize(10.5);

        // Section A4 Paysage pour une excellente lisibilité du tableau
        $this->section = $this->phpWord->addSection([
            'orientation' => 'landscape',
            'marginLeft' => 1134, // ~2cm
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);

        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter(
                $this->section,
                $enterprise,
                'process_cartography',
                0,
                $documentMeta
            );
        }

        // 1. Titre du document
        $this->addHeaderTitle($cartographyData, $enterprise, $documentMeta);

        // 2. Tableau Officiel conforme au canevas utilisateur
        $this->addCartographyTable($cartographyData);

        // 3. Pied de page / Signatures
        $this->addSignaturesBlock();

        $tempFile = tempnam(sys_get_temp_dir(), 'cartographie_processus_') . '.docx';
        $this->phpWord->save($tempFile, 'Word2007');

        return $tempFile;
    }

    private function addHeaderTitle(array $data, ?Enterprise $enterprise, array $documentMeta = [])
    {
        $enterpriseName = $enterprise?->name ?? $data['enterprise_name'] ?? 'Système de Management de la Qualité';
        
        $this->section->addText(
            mb_strtoupper($enterpriseName),
            ['bold' => true, 'size' => 12, 'color' => '1E3A8A'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );

        $this->section->addText(
            'CARTOGRAPHIE DES PROCESSUS — SYNTHÈSE & RESPONSABILITÉS',
            ['bold' => true, 'size' => 14, 'color' => '0F172A'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );

        if (!empty($documentMeta['code'])) {
            $metaParts = [];
            $metaParts[] = 'Réf Doc : ' . $documentMeta['code'];
            $metaParts[] = 'Version : ' . ($documentMeta['version'] ?? '1.0');
            if (!empty($documentMeta['effective_date'])) {
                $metaParts[] = 'Date d\'application : ' . $documentMeta['effective_date'];
            }
            $this->section->addText(
                implode('   |   ', $metaParts),
                ['bold' => true, 'size' => 9.5, 'color' => '1E293B'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
            );
        }

        $this->section->addText(
            'Conforme à la norme ISO 9001:2015 (§4.4) — Cartographie et interactions des processus',
            ['italic' => true, 'size' => 9.5, 'color' => '64748B'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
    }

    private function addCartographyTable(array $data)
    {
        $processes = $data['all_processes'] ?? [];

        // Styles de tableau
        $tableStyle = [
            'borderColor' => 'CBD5E1',
            'borderSize'  => 6,
            'cellMarginTop' => 100,
            'cellMarginRight' => 120,
            'cellMarginBottom' => 100,
            'cellMarginLeft' => 120,
            'alignment' => JcTable::CENTER,
        ];
        $this->phpWord->addTableStyle('CartographyTable', $tableStyle);
        $table = $this->section->addTable('CartographyTable');

        // Largeurs des colonnes en twips (Total paysage ~14000 twips)
        $wNom = 3400;         // PROPOSITION DE NOM PROCESSUS
        $wActivites = 5600;   // ACTIVITES PRINCIPALES
        $wObs = 2800;         // OBSERVATION
        $wResp = 2600;        // RESPONSABLE PAR DEPARTEMENT

        // En-tête du tableau (Bleu marine professionnel)
        $headerCellStyle = ['bgColor' => '1E293B', 'valign' => 'center'];
        $headerFontStyle = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9.5];

        $table->addRow(450, ['tblHeader' => true, 'cantSplit' => true]);
        $table->addCell($wNom, $headerCellStyle)->addText('PROPOSITION DE NOM PROCESSUS', $headerFontStyle, ['alignment' => Jc::CENTER]);
        $table->addCell($wActivites, $headerCellStyle)->addText('ACTIVITES PRINCIPALES', $headerFontStyle, ['alignment' => Jc::CENTER]);
        $table->addCell($wObs, $headerCellStyle)->addText('OBSERVATION', $headerFontStyle, ['alignment' => Jc::CENTER]);
        $table->addCell($wResp, $headerCellStyle)->addText('RESPONSABLE PAR DEPARTEMENT', $headerFontStyle, ['alignment' => Jc::CENTER]);

        // Lignes de processus
        $rowIndex = 0;
        foreach ($processes as $proc) {
            $rowIndex++;
            $bg = ($rowIndex % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $cellStyle = ['bgColor' => $bg, 'valign' => 'top'];

            $table->addRow();

            // 1. PROPOSITION DE NOM PROCESSUS
            $cNom = $table->addCell($wNom, $cellStyle);
            $cNom->addText(
                mb_strtoupper($proc['title'] ?? $proc['name'] ?? 'PROCESSUS'),
                ['bold' => true, 'size' => 9.5, 'color' => '0F172A'],
                ['spaceAfter' => 20]
            );
            if (!empty($proc['code'])) {
                $cNom->addText(
                    'Code : ' . $proc['code'],
                    ['size' => 8, 'color' => '64748B', 'italic' => true],
                    ['spaceAfter' => 0]
                );
            }

            // 2. ACTIVITES PRINCIPALES
            $cAct = $table->addCell($wActivites, $cellStyle);
            $activitiesText = $proc['activities_summary'] ?? $proc['finalite'] ?? 'Activités opérationnelles du processus';
            $cAct->addText(
                $activitiesText,
                ['size' => 9, 'color' => '334155'],
                ['spaceAfter' => 0]
            );

            // 3. OBSERVATION
            $cObs = $table->addCell($wObs, $cellStyle);
            $observationText = !empty($proc['observation']) ? $proc['observation'] : '';
            $cObs->addText(
                $observationText,
                ['size' => 9, 'color' => '334155', 'italic' => empty($observationText)],
                ['spaceAfter' => 0]
            );

            // 4. RESPONSABLE PAR DEPARTEMENT
            $cResp = $table->addCell($wResp, $cellStyle);
            $respName = $proc['responsible_display'] ?? $proc['pilot_name'] ?? 'Non assigné';
            $cResp->addText(
                $respName,
                ['bold' => true, 'size' => 9, 'color' => '0F172A'],
                ['spaceAfter' => 20]
            );
            if (!empty($proc['department'])) {
                $cResp->addText(
                    $proc['department'],
                    ['size' => 8, 'color' => '64748B'],
                    ['spaceAfter' => 0]
                );
            }
        }

        $this->section->addTextBreak(1);
    }

    private function addSignaturesBlock()
    {
        $table = $this->section->addTable([
            'borderSize' => 6,
            'borderColor' => 'E2E8F0',
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow(900, ['cantSplit' => true]);

        $c1 = $table->addCell(7200, ['bgColor' => 'F8FAFC', 'valign' => 'top']);
        $c1->addText('Établi par le Responsable Qualité / Pilote SMQ :', ['bold' => true, 'size' => 9, 'color' => '475569']);
        $c1->addTextBreak(2);
        $c1->addText('Date & Signature :', ['italic' => true, 'size' => 8, 'color' => '94A3B8']);

        $c2 = $table->addCell(7200, ['bgColor' => 'F8FAFC', 'valign' => 'top']);
        $c2->addText('Approuvé par la Direction Générale :', ['bold' => true, 'size' => 9, 'color' => '475569']);
        $c2->addTextBreak(2);
        $c2->addText('Date, Cachet & Signature :', ['italic' => true, 'size' => 8, 'color' => '94A3B8']);
    }
}

