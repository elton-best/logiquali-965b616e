<?php

namespace App\Modules\Evaluation\Services;

use App\Models\Report;
use App\Services\DocumentBrandingService;

use App\Models\ManagementReview;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class ManagementReviewDocxGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    /**
     * Generate Management Review Report DOCX (ISO 9001 compliant)
     */
    public function generateReport(ManagementReview $review): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'marginTop' => 1000,
            'marginBottom' => 1000,
        ]);

        $enterprise = $review->site?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // Header
        $headerStyle = ['bold' => true, 'size' => 16, 'color' => '2E3B55'];
        $section->addText('RAPPORT DE REVUE DE DIRECTION', $headerStyle, ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        // Metadata Table
        $metaTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Référence:', ['bold' => true]);
        $metaTable->addCell(7000)->addText($review->ref ?? 'N/A');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date Planifiée:', ['bold' => true]);
        $metaTable->addCell(7000)->addText(\Carbon\Carbon::parse($review->planned_date)->format('d/m/Y'));
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date Réelle:', ['bold' => true]);
        $metaTable->addCell(7000)->addText(
            $review->actual_date ? \Carbon\Carbon::parse($review->actual_date)->format('d/m/Y') : 'En cours'
        );
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Président:', ['bold' => true]);
        $metaTable->addCell(7000)->addText($review->chairman->name ?? 'N/A');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Participants:', ['bold' => true]);
        $metaTable->addCell(7000)->addText($this->formatParticipants($review->participants));
        
        $section->addTextBreak(2);

        // SECTION 1: DONNÉES D'ENTRÉE (ISO 9001 Clause 9.3.2)
        $this->addSection($section, '1. DONNÉES D\'ENTRÉE DE LA REVUE DE DIRECTION');
        
        $this->addSubSection($section, '1.1 État des Actions des Revues Précédentes');
        $section->addText($review->previous_actions_status ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.2 Changements dans le Contexte de l\'Organisme');
        $section->addText($review->context_changes ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.3 Informations sur les Performances et l\'Efficacité du SMQ');
        $section->addText($review->performance_indicators ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.4 Satisfaction Client');
        $section->addText($review->customer_satisfaction ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.5 Résultats des Audits');
        $section->addText($review->audit_results ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.6 État des Non-Conformités et Réclamations');
        $section->addText($review->nc_complaints_status ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.7 Adéquation des Ressources');
        $section->addText($review->resources_adequacy ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(1);
        
        $this->addSubSection($section, '1.8 Opportunités d\'Amélioration');
        $section->addText($review->improvement_opportunities ?? 'Non renseigné', ['size' => 10]);
        $section->addTextBreak(2);

        // SECTION 2: DÉCISIONS ET ACTIONS (ISO 9001 Clause 9.3.3)
        $this->addSection($section, '2. DÉCISIONS ET ACTIONS DE LA REVUE DE DIRECTION');
        
        if ($review->decisions && is_array($review->decisions) && count($review->decisions) > 0) {
            $decisionTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'cellMargin' => 80,
            ]);
            
            // Header
            $decisionTable->addRow(600);
            $headerCellStyle = ['bgColor' => '4472C4', 'valign' => 'center'];
            $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 10];
            
            $decisionTable->addCell(1000, $headerCellStyle)->addText('N°', $headerFont);
            $decisionTable->addCell(3000, $headerCellStyle)->addText('DÉCISION', $headerFont);
            $decisionTable->addCell(2500, $headerCellStyle)->addText('ACTIONS', $headerFont);
            $decisionTable->addCell(2000, $headerCellStyle)->addText('RESPONSABLE', $headerFont);
            $decisionTable->addCell(1500, $headerCellStyle)->addText('DÉLAI', $headerFont);
            
            // Data
            foreach ($review->decisions as $index => $decision) {
                $decisionTable->addRow();
                $decisionTable->addCell(1000)->addText($index + 1);
                $decisionTable->addCell(3000)->addText($decision['decision'] ?? 'N/A');
                $decisionTable->addCell(2500)->addText($decision['actions'] ?? 'N/A');
                $decisionTable->addCell(2000)->addText($decision['responsible'] ?? 'N/A');
                $decisionTable->addCell(1500)->addText($decision['deadline'] ?? 'N/A');
            }
        } else {
            $section->addText('Aucune décision enregistrée.', ['italic' => true, 'color' => '666666']);
        }
        
        $section->addTextBreak(2);

        // SECTION 3: CONCLUSION
        $this->addSection($section, '3. CONCLUSION');
        $section->addText(
            'Cette revue de direction a permis d\'évaluer l\'efficacité du Système de Management de la Qualité '
            . 'conformément à la norme ISO 9001:2015, clause 9.3.',
            ['size' => 10]
        );
        $section->addTextBreak(1);
        
        $section->addText('Les décisions et actions définies ci-dessus doivent être suivies et leur efficacité évaluée lors de la prochaine revue de direction.', ['size' => 10]);
        
        $section->addTextBreak(2);

        // Signatures
        $signTable = $section->addTable();
        $signTable->addRow();
        $signTable->addCell(5000)->addText('Président de Revue', ['bold' => true, 'size' => 10]);
        $signTable->addCell(5000)->addText('Responsable Qualité', ['bold' => true, 'size' => 10]);
        
        $signTable->addRow(1500);
        $signTable->addCell(5000);
        $signTable->addCell(5000);
        
        $signTable->addRow();
        $signTable->addCell(5000)->addText('Signature:', ['size' => 9]);
        $signTable->addCell(5000)->addText('Signature:', ['size' => 9]);

        // Save
        $fileName = 'rapport_revue_direction_' . ($review->ref ?? 'RRD') . '_' . now()->format('Y-m-d') . '.docx';
        $tempPath = storage_path('app/temp/' . $fileName);
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    /**
     * Add main section title
     */
    private function addSection($section, string $title): void
    {
        $section->addText(
            $title,
            ['bold' => true, 'size' => 12, 'color' => '2E3B55'],
            ['spaceBefore' => 200, 'spaceAfter' => 200]
        );
    }

    /**
     * Add subsection title
     */
    private function addSubSection($section, string $title): void
    {
        $section->addText(
            $title,
            ['bold' => true, 'size' => 11, 'color' => '4472C4'],
            ['spaceBefore' => 100, 'spaceAfter' => 100]
        );
    }

    /**
     * Format participants array
     */
    private function formatParticipants($participants): string
    {
        if (!$participants) return 'Non renseigné';
        
        if (is_array($participants)) {
            return implode(', ', $participants);
        }
        
        return (string) $participants;
    }
}
