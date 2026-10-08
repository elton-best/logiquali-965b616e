<?php

namespace App\Services;

use App\Models\Stakeholder;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

class StakeholderDocxGenerator
{
    /**
     * Generate "Registre des Parties Intéressées" DOCX
     */
    public function generateRegistry(Collection $stakeholders): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginLeft' => 800,
            'marginRight' => 800,
            'marginTop' => 800,
            'marginBottom' => 800,
        ]);

        // Header
        $headerStyle = ['bold' => true, 'size' => 14, 'color' => '2E3B55'];
        $section->addText(
            'REGISTRE DES PARTIES INTÉRESSÉES',
            $headerStyle,
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Metadata
        $section->addText(
            'Date : ' . now()->format('d/m/Y'),
            ['size' => 10],
            ['alignment' => Jc::RIGHT]
        );
        $section->addTextBreak(1);

        // Table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
            'width' => 100 * 50,
            'unit' => 'pct',
        ];

        $table = $section->addTable($tableStyle);

        // Header row
        $table->addRow(800);
        $headerCellStyle = ['bgColor' => '2E3B55', 'valign' => 'center'];
        $headerFontStyle = ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'];

        $table->addCell(3000, $headerCellStyle)->addText('PARTIE INTÉRESSÉE', $headerFontStyle);
        $table->addCell(1500, $headerCellStyle)->addText('TYPE', $headerFontStyle);
        $table->addCell(2000, $headerCellStyle)->addText('DEGRÉ PERTINENCE', $headerFontStyle);
        $table->addCell(3000, $headerCellStyle)->addText('BESOINS & ATTENTES', $headerFontStyle);
        $table->addCell(3000, $headerCellStyle)->addText('EXIGENCES', $headerFontStyle);
        $table->addCell(3000, $headerCellStyle)->addText('ACTIONS', $headerFontStyle);
        $table->addCell(2000, $headerCellStyle)->addText('RESPONSABLE', $headerFontStyle);
        $table->addCell(1500, $headerCellStyle)->addText('DÉLAI', $headerFontStyle);

        // Data rows
        $cellStyle = ['valign' => 'center'];
        $fontStyle = ['size' => 9];

        foreach ($stakeholders as $stakeholder) {
            $table->addRow();
            
            $table->addCell(3000, $cellStyle)->addText(
                $stakeholder->name ?? '',
                $fontStyle
            );
            
            $table->addCell(1500, $cellStyle)->addText(
                $this->translateType($stakeholder->type),
                $fontStyle
            );
            
            // Relevance with color
            $relevanceColor = $this->getRelevanceColor($stakeholder->relevance_degree);
            $relevanceCell = $table->addCell(2000, array_merge($cellStyle, ['bgColor' => $relevanceColor]));
            $relevanceCell->addText(
                $stakeholder->relevance_degree ?? 'N/A',
                $fontStyle
            );
            
            $table->addCell(3000, $cellStyle)->addText(
                $this->truncate($stakeholder->needs_expectations, 150),
                $fontStyle
            );
            
            $table->addCell(3000, $cellStyle)->addText(
                $this->truncate($stakeholder->requirements, 150),
                $fontStyle
            );
            
            $table->addCell(3000, $cellStyle)->addText(
                $this->truncate($stakeholder->actions, 150),
                $fontStyle
            );
            
            $table->addCell(2000, $cellStyle)->addText(
                $stakeholder->responsible->name ?? 'N/A',
                $fontStyle
            );
            
            $table->addCell(1500, $cellStyle)->addText(
                $stakeholder->deadline ? \Carbon\Carbon::parse($stakeholder->deadline)->format('d/m/Y') : 'N/A',
                $fontStyle
            );
        }

        // Footer legend
        $section->addTextBreak(1);
        $section->addText('Légende des couleurs:', ['bold' => true, 'size' => 9]);
        $section->addText('• Rouge : Pertinence Critique', ['size' => 8, 'color' => 'FF0000']);
        $section->addText('• Orange : Pertinence Élevée', ['size' => 8, 'color' => 'FFA500']);
        $section->addText('• Jaune : Pertinence Moyenne', ['size' => 8, 'color' => 'FFD700']);
        $section->addText('• Vert : Pertinence Faible', ['size' => 8, 'color' => '008000']);

        // Save
        $fileName = 'registre_parties_interessees_' . now()->format('Y-m-d') . '.docx';
        $tempPath = storage_path('app/temp/' . $fileName);
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    /**
     * Translate stakeholder type to French
     */
    private function translateType(string $type): string
    {
        $types = [
            'clientb' => 'Client',
            'supplier' => 'Fournisseur',
            'partner' => 'Partenaire',
            'regulator' => 'Organisme Réglementaire',
            'employee' => 'Employé',
            'shareholder' => 'Actionnaire',
            'other' => 'Autre',
        ];

        return $types[$type] ?? ucfirst($type);
    }

    /**
     * Get color based on relevance degree
     */
    private function getRelevanceColor(?string $relevance): string
    {
        if (!$relevance) return 'FFFFFF';

        $relevance = strtolower($relevance);

        if (str_contains($relevance, 'critique') || str_contains($relevance, 'critical')) {
            return 'FFCCCC'; // Light red
        }
        if (str_contains($relevance, 'élevé') || str_contains($relevance, 'high')) {
            return 'FFE5CC'; // Light orange
        }
        if (str_contains($relevance, 'moyen') || str_contains($relevance, 'medium')) {
            return 'FFFFCC'; // Light yellow
        }
        if (str_contains($relevance, 'faible') || str_contains($relevance, 'low')) {
            return 'CCFFCC'; // Light green
        }

        return 'FFFFFF';
    }

    /**
     * Truncate text to specified length
     */
    private function truncate(?string $text, int $length): string
    {
        if (!$text) return 'N/A';
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }
}
