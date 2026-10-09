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
        $this->addSection($section, '1. DONNÉES D\'ENTRÉE DE LA REVUE DE DIRECTION (ISO 9001 §9.3.2)');
        
        $entries = $review->input_data['iso_9001_9_3_2']['entries'] ?? ($review->input_data['entries'] ?? []);

        $inputItems = [
            ['code' => 'a', 'num' => '1.1', 'title' => 'Actions des Revues Précédentes (§9.3.2.a)', 'fallback' => $review->previous_actions_status],
            ['code' => 'b', 'num' => '1.2', 'title' => 'Changements dans le Contexte de l\'Organisme (§9.3.2.b)', 'fallback' => $review->context_changes],
            ['code' => 'c1', 'num' => '1.3', 'title' => 'Satisfaction Client et Parties Intéressées (§9.3.2.c1)', 'fallback' => $review->customer_satisfaction],
            ['code' => 'c2', 'num' => '1.4', 'title' => 'Atteinte des Objectifs Qualité (§9.3.2.c2)', 'fallback' => null],
            ['code' => 'c4', 'num' => '1.5', 'title' => 'Performance des Processus et Conformité (§9.3.2.c4)', 'fallback' => $review->performance_indicators],
            ['code' => 'c5', 'num' => '1.6', 'title' => 'Non-Conformités et Réclamations (§9.3.2.c5)', 'fallback' => $review->nc_complaints_status],
            ['code' => 'c6', 'num' => '1.7', 'title' => 'Résultats des Audits (§9.3.2.c6)', 'fallback' => $review->audit_results],
            ['code' => 'c7', 'num' => '1.8', 'title' => 'Performance des Prestataires Externes (§9.3.2.c7)', 'fallback' => null],
            ['code' => 'e', 'num' => '1.9', 'title' => 'Efficacité des Actions Risques & Opportunités (§9.3.2.e)', 'fallback' => null],
            ['code' => 'f', 'num' => '1.10', 'title' => 'Opportunités d\'Amélioration (§9.3.2.f)', 'fallback' => $review->improvement_opportunities],
        ];

        foreach ($inputItems as $item) {
            $this->addSubSection($section, $item['num'] . ' ' . $item['title']);
            $entry = $entries[$item['code']] ?? null;
            $obs = $entry['synthese_observations'] ?? ($entry['summary'] ?? ($item['fallback'] ?? 'Non renseigné'));
            $decision = $entry['decision_action'] ?? null;
            $resp = $entry['responsable'] ?? null;
            $delai = $entry['delai'] ?? null;

            $section->addText('• Synthèse / Observations : ' . $obs, ['size' => 10]);
            if ($decision) {
                $section->addText('• Décision / Action : ' . $decision, ['size' => 10, 'bold' => true]);
            }
            if ($resp || $delai) {
                $section->addText(sprintf('• Responsable : %s | Délai : %s', $resp ?? 'N/A', $delai ?? 'N/A'), ['size' => 9, 'italic' => true]);
            }
            $section->addTextBreak(1);
        }

        // SECTION 2: BESOINS ET RESSOURCES (ISO 9001 Clause 9.3.3.b)
        $this->addSection($section, '2. BESOINS ET RESSOURCES (ISO 9001 §9.3.3.b)');
        $resData = $review->resources_data ?? [];
        $resObs = $resData['synthese_observations'] ?? ($review->resources_adequacy ?? 'Ressources évaluées adéquates.');
        $resDec = $resData['decision_action'] ?? 'Maintenir les moyens alloués.';
        $resResp = $resData['responsable'] ?? 'Direction Générale';
        $resDelai = $resData['delai'] ?? 'Permanent';

        $resTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $resTable->addRow();
        $resTable->addCell(3000)->addText('Synthèse / Observations:', ['bold' => true, 'size' => 9]);
        $resTable->addCell(7000)->addText($resObs, ['size' => 9]);
        $resTable->addRow();
        $resTable->addCell(3000)->addText('Décision / Action:', ['bold' => true, 'size' => 9]);
        $resTable->addCell(7000)->addText($resDec, ['size' => 9]);
        $resTable->addRow();
        $resTable->addCell(3000)->addText('Responsable & Délai:', ['bold' => true, 'size' => 9]);
        $resTable->addCell(7000)->addText(sprintf('%s | Délai: %s', $resResp, $resDelai), ['size' => 9]);
        $section->addTextBreak(2);

        // SECTION 3: MODIFICATIONS DU SYSTÈME (ISO 9001 Clause 9.3.3.c)
        $this->addSection($section, '3. MODIFICATIONS DU SYSTÈME (ISO 9001 §9.3.3.c)');
        $sysChanges = $review->system_changes_data ?? [];
        $besoinsChangements = $sysChanges['besoins_changements_systeme'] ?? [];
        $autresBesoins = $sysChanges['autres_besoins_changements_systeme'] ?? [];

        $this->addSubSection($section, '3.1 Besoins de changement à apporter au système');
        if (!empty($besoinsChangements) && is_array($besoinsChangements)) {
            $chgTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            $chgTable->addRow(500);
            $hdrStyle = ['bgColor' => '4472C4'];
            $hdrFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9];
            $chgTable->addCell(5000, $hdrStyle)->addText('DÉCISION / ACTION', $hdrFont);
            $chgTable->addCell(3000, $hdrStyle)->addText('RESPONSABLE', $hdrFont);
            $chgTable->addCell(2000, $hdrStyle)->addText('DÉLAI', $hdrFont);
            foreach ($besoinsChangements as $chg) {
                $chgTable->addRow();
                $chgTable->addCell(5000)->addText($chg['decision_action'] ?? 'N/A', ['size' => 9]);
                $chgTable->addCell(3000)->addText($chg['responsable'] ?? 'N/A', ['size' => 9]);
                $chgTable->addCell(2000)->addText($chg['delai'] ?? 'N/A', ['size' => 9]);
            }
        } else {
            $section->addText('Aucun besoin de changement spécifique identifié.', ['italic' => true, 'color' => '666666', 'size' => 9]);
        }
        $section->addTextBreak(1);

        $this->addSubSection($section, '3.2 Autres besoins de changements à apporter au système');
        if (!empty($autresBesoins) && is_array($autresBesoins)) {
            $autreTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            $autreTable->addRow(500);
            $hdrStyle = ['bgColor' => '5B9BD5'];
            $hdrFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9];
            $autreTable->addCell(5000, $hdrStyle)->addText('DÉCISION / ACTION', $hdrFont);
            $autreTable->addCell(3000, $hdrStyle)->addText('RESPONSABLE', $hdrFont);
            $autreTable->addCell(2000, $hdrStyle)->addText('DÉLAI', $hdrFont);
            foreach ($autresBesoins as $chg) {
                $autreTable->addRow();
                $autreTable->addCell(5000)->addText($chg['decision_action'] ?? 'N/A', ['size' => 9]);
                $autreTable->addCell(3000)->addText($chg['responsable'] ?? 'N/A', ['size' => 9]);
                $autreTable->addCell(2000)->addText($chg['delai'] ?? 'N/A', ['size' => 9]);
            }
        } else {
            $section->addText('Aucun autre besoin de changement identifié.', ['italic' => true, 'color' => '666666', 'size' => 9]);
        }
        $section->addTextBreak(2);

        // SECTION 4: DÉCISIONS ET ACTIONS GÉNÉRALES (ISO 9001 Clause 9.3.3.a)
        $this->addSection($section, '4. DÉCISIONS ET ACTIONS GÉNÉRALES DE LA REVUE DE DIRECTION');
        
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

        // SECTION 5: CONCLUSION
        $this->addSection($section, '5. CONCLUSION');
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
