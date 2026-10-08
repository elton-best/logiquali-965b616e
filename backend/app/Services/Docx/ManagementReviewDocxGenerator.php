<?php

namespace App\Services\Docx;

use App\Models\ManagementReview;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

/**
 * Générateur DOCX pour Rapport Revue de Direction (M12-D2)
 * 
 * Structure conforme ISO 9001:2015 clause 9.3:
 * - Informations générales (date, participants, ordre du jour)
 * - Éléments d'entrée (performances, satisfaction, audits, risques)
 * - Décisions et actions (amélioration, ressources, changements)
 * - Plan d'action avec responsabilités et délais
 */
class ManagementReviewDocxGenerator
{
    private PhpWord $phpWord;
    
    public function __construct()
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Calibri');
        $this->phpWord->setDefaultFontSize(11);
    }
    
    /**
     * Génère le rapport de revue de direction
     */
    public function generate(ManagementReview $review): string
    {
        $section = $this->phpWord->addSection([
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);
        
        // En-tête
        $this->addHeader($section, $review);
        
        // 1. Informations générales
        $this->addGeneralInfo($section, $review);
        
        $section->addPageBreak();
        
        // 2. Éléments d'entrée
        $this->addInputElements($section, $review);
        
        $section->addPageBreak();
        
        // 3. Décisions et actions
        $this->addDecisionsActions($section, $review);
        
        $section->addPageBreak();
        
        // 4. Plan d'action
        $this->addActionPlan($section, $review);
        
        // 5. Signatures
        $this->addSignatures($section, $review);
        
        // Sauvegarde
        $filename = storage_path('app/temp/rapport_revue_direction_' . $review->id . '_' . time() . '.docx');
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($filename);
        
        return $filename;
    }
    
    /**
     * En-tête du document
     */
    private function addHeader($section, ManagementReview $review): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        
        $table->addRow(500);
        $cell1 = $table->addCell(2500);
        $cell1->addText('LOGO ENTREPRISE', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        
        $cell2 = $table->addCell(7000);
        $cell2->addText(
            'RAPPORT DE REVUE DE DIRECTION',
            ['bold' => true, 'size' => 16, 'color' => '0066CC'],
            ['alignment' => Jc::CENTER]
        );
        $cell2->addText(
            'Conforme ISO 9001:2015 - Clause 9.3',
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(2);
    }
    
    /**
     * Section 1: Informations générales
     */
    private function addGeneralInfo($section, ManagementReview $review): void
    {
        $section->addText('1. INFORMATIONS GÉNÉRALES', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
        ]);
        
        // Date
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('Date de la revue', ['bold' => true]);
        $table->addCell(7000)->addText($review->date_revue->format('d/m/Y'), ['size' => 11]);
        
        // Période couverte
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('Période couverte', ['bold' => true]);
        $table->addCell(7000)->addText($review->periode ?? 'Non spécifiée', ['size' => 11]);
        
        // Type
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('Type de revue', ['bold' => true]);
        $typeLabel = match($review->type) {
            'trimestrielle' => 'Trimestrielle',
            'semestrielle' => 'Semestrielle',
            'annuelle' => 'Annuelle',
            'extraordinaire' => 'Extraordinaire',
            default => ucfirst($review->type),
        };
        $table->addCell(7000)->addText($typeLabel, ['size' => 11]);
        
        // Statut
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('Statut', ['bold' => true]);
        $statusColor = $review->statut === 'cloturee' ? '00CC00' : 'FF9900';
        $table->addCell(7000)->addText(
            ucfirst($review->statut),
            ['bold' => true, 'color' => $statusColor, 'size' => 11]
        );
        
        $section->addTextBreak();
        
        // Participants
        $section->addText('Participants:', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $participantsTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 50,
        ]);
        
        $participantsTable->addRow();
        $participantsTable->addCell(4000, ['bgColor' => 'E7E6E6'])->addText('Nom', ['bold' => true]);
        $participantsTable->addCell(3000, ['bgColor' => 'E7E6E6'])->addText('Fonction', ['bold' => true]);
        $participantsTable->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('Présence', ['bold' => true], ['alignment' => Jc::CENTER]);
        
        $participants = $review->participants ?? [];
        if (is_array($participants) && count($participants) > 0) {
            foreach ($participants as $participant) {
                $participantsTable->addRow();
                $participantsTable->addCell(4000)->addText($participant['nom'] ?? 'N/A', ['size' => 10]);
                $participantsTable->addCell(3000)->addText($participant['fonction'] ?? 'N/A', ['size' => 10]);
                
                $presence = $participant['presence'] ?? true;
                $presenceText = $presence ? '✓ Présent' : '✗ Absent';
                $presenceColor = $presence ? '00CC00' : 'FF0000';
                $participantsTable->addCell(2500)->addText(
                    $presenceText,
                    ['color' => $presenceColor, 'size' => 10],
                    ['alignment' => Jc::CENTER]
                );
            }
        } else {
            $participantsTable->addRow();
            $participantsTable->addCell(9500, ['gridSpan' => 3])->addText(
                'Aucun participant enregistré',
                ['italic' => true, 'color' => '999999'],
                ['alignment' => Jc::CENTER]
            );
        }
        
        $section->addTextBreak();
        
        // Ordre du jour
        $section->addText('Ordre du jour:', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $agenda = $review->ordre_du_jour ?? [];
        if (is_array($agenda) && count($agenda) > 0) {
            foreach ($agenda as $idx => $item) {
                $section->addListItem(
                    ($idx + 1) . '. ' . $item,
                    0,
                    ['size' => 11]
                );
            }
        } else {
            $section->addText('Ordre du jour non défini', ['italic' => true, 'color' => '999999']);
        }
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 2: Éléments d'entrée
     */
    private function addInputElements($section, ManagementReview $review): void
    {
        $section->addText('2. ÉLÉMENTS D\'ENTRÉE DE LA REVUE', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $elements = [
            'performance_processus' => [
                'titre' => '2.1 Performance des processus',
                'data' => $review->performance_processus,
            ],
            'satisfaction_client' => [
                'titre' => '2.2 Satisfaction client',
                'data' => $review->satisfaction_client,
            ],
            'resultats_audits' => [
                'titre' => '2.3 Résultats des audits',
                'data' => $review->resultats_audits,
            ],
            'non_conformites' => [
                'titre' => '2.4 Non-conformités et actions correctives',
                'data' => $review->non_conformites,
            ],
            'risques_opportunites' => [
                'titre' => '2.5 Risques et opportunités',
                'data' => $review->risques_opportunites,
            ],
            'ameliorations_continues' => [
                'titre' => '2.6 Améliorations continues',
                'data' => $review->ameliorations_continues,
            ],
            'changements_contexte' => [
                'titre' => '2.7 Changements dans le contexte',
                'data' => $review->changements_contexte,
            ],
            'ressources' => [
                'titre' => '2.8 Adéquation des ressources',
                'data' => $review->ressources,
            ],
        ];
        
        foreach ($elements as $key => $element) {
            $section->addText($element['titre'], ['bold' => true, 'size' => 12]);
            $section->addTextBreak();
            
            $content = $element['data'] ?? 'Non renseigné';
            $section->addText($content, ['size' => 11], ['alignment' => Jc::BOTH]);
            
            $section->addTextBreak(1);
        }
    }
    
    /**
     * Section 3: Décisions et actions
     */
    private function addDecisionsActions($section, ManagementReview $review): void
    {
        $section->addText('3. DÉCISIONS ET ACTIONS', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        // 3.1 Opportunités d'amélioration
        $section->addText('3.1 Opportunités d\'amélioration', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $ameliorations = $review->decisions_ameliorations ?? [];
        if (is_array($ameliorations) && count($ameliorations) > 0) {
            foreach ($ameliorations as $idx => $item) {
                $section->addListItem(($idx + 1) . '. ' . $item, 0, ['size' => 11]);
            }
        } else {
            $section->addText('Aucune amélioration identifiée', ['italic' => true, 'color' => '999999']);
        }
        
        $section->addTextBreak(1);
        
        // 3.2 Besoins en ressources
        $section->addText('3.2 Besoins en ressources', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $ressources = $review->decisions_ressources ?? [];
        if (is_array($ressources) && count($ressources) > 0) {
            foreach ($ressources as $idx => $item) {
                $section->addListItem(($idx + 1) . '. ' . $item, 0, ['size' => 11]);
            }
        } else {
            $section->addText('Aucun besoin identifié', ['italic' => true, 'color' => '999999']);
        }
        
        $section->addTextBreak(1);
        
        // 3.3 Changements SMI
        $section->addText('3.3 Changements nécessaires au SMI', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $changements = $review->decisions_changements ?? [];
        if (is_array($changements) && count($changements) > 0) {
            foreach ($changements as $idx => $item) {
                $section->addListItem(($idx + 1) . '. ' . $item, 0, ['size' => 11]);
            }
        } else {
            $section->addText('Aucun changement nécessaire', ['italic' => true, 'color' => '999999']);
        }
        
        $section->addTextBreak(1);
        
        // Conclusion globale
        $section->addText('Conclusion globale:', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        $section->addText(
            $review->conclusion ?? 'Non renseignée',
            ['size' => 11],
            ['alignment' => Jc::BOTH]
        );
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 4: Plan d'action
     */
    private function addActionPlan($section, ManagementReview $review): void
    {
        $section->addText('4. PLAN D\'ACTION ISSU DE LA REVUE', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);
        
        // Header
        $table->addRow(600);
        $table->addCell(500, ['bgColor' => '0066CC'])->addText('N°', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(3500, ['bgColor' => '0066CC'])->addText('ACTION', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => '0066CC'])->addText('RESPONSABLE', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1000, ['bgColor' => '0066CC'])->addText('DÉLAI', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1000, ['bgColor' => '0066CC'])->addText('PRIORITÉ', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => '0066CC'])->addText('STATUT', ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        
        // Actions
        $actions = $review->actions ?? [];
        if (is_array($actions) && count($actions) > 0) {
            foreach ($actions as $idx => $action) {
                $table->addRow();
                $table->addCell(500)->addText($idx + 1, ['size' => 9], ['alignment' => Jc::CENTER]);
                $table->addCell(3500)->addText($action['titre'] ?? 'N/A', ['size' => 9]);
                $table->addCell(1500)->addText($action['responsable'] ?? 'Non assigné', ['size' => 9]);
                $table->addCell(1000)->addText($action['delai'] ?? 'N/A', ['size' => 9], ['alignment' => Jc::CENTER]);
                
                $priorite = $action['priorite'] ?? 'moyenne';
                $prioriteColor = match($priorite) {
                    'haute' => 'FF0000',
                    'moyenne' => 'FF9900',
                    'basse' => '00CC00',
                    default => 'CCCCCC',
                };
                $table->addCell(1000, ['bgColor' => $prioriteColor])->addText(
                    ucfirst($priorite),
                    ['bold' => true, 'color' => 'FFFFFF', 'size' => 9],
                    ['alignment' => Jc::CENTER]
                );
                
                $statut = $action['statut'] ?? 'en_cours';
                $table->addCell(1500)->addText(ucfirst(str_replace('_', ' ', $statut)), ['size' => 9], ['alignment' => Jc::CENTER]);
            }
        } else {
            $table->addRow();
            $table->addCell(9000, ['gridSpan' => 6])->addText(
                'Aucune action définie',
                ['italic' => true, 'color' => '999999'],
                ['alignment' => Jc::CENTER]
            );
        }
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 5: Signatures
     */
    private function addSignatures($section, ManagementReview $review): void
    {
        $section->addPageBreak();
        
        $section->addText('5. VALIDATION ET SIGNATURES', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak(2);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 100,
        ]);
        
        // Président de séance
        $table->addRow(1500);
        $cell1 = $table->addCell(4750);
        $cell1->addText('Président de séance:', ['bold' => true, 'size' => 11]);
        $cell1->addTextBreak();
        $cell1->addText($review->president->name ?? 'Non défini', ['size' => 11]);
        $cell1->addTextBreak(2);
        $cell1->addText('Signature:', ['size' => 10]);
        $cell1->addTextBreak();
        $cell1->addText('Date: ' . $review->date_revue->format('d/m/Y'), ['size' => 10]);
        
        // Responsable Qualité
        $cell2 = $table->addCell(4750);
        $cell2->addText('Responsable Qualité:', ['bold' => true, 'size' => 11]);
        $cell2->addTextBreak();
        $cell2->addText($review->responsable_qualite->name ?? 'Non défini', ['size' => 11]);
        $cell2->addTextBreak(2);
        $cell2->addText('Signature:', ['size' => 10]);
        $cell2->addTextBreak();
        $cell2->addText('Date: ' . $review->date_revue->format('d/m/Y'), ['size' => 10]);
        
        $section->addTextBreak(2);
        
        // Note de diffusion
        $section->addText('Note de diffusion', ['bold' => true, 'size' => 12, 'underline' => 'single']);
        $section->addTextBreak();
        
        $section->addText(
            'Ce rapport de revue de direction a été établi le ' . now()->format('d/m/Y') . ' ' .
            'conformément aux exigences de la norme ISO 9001:2015, clause 9.3.',
            ['size' => 10, 'italic' => true],
            ['alignment' => Jc::BOTH]
        );
        
        $section->addTextBreak();
        
        $section->addText('Distribution:', ['bold' => true, 'size' => 10]);
        $section->addListItem('Direction Générale', 0, ['size' => 10]);
        $section->addListItem('Responsable Qualité', 0, ['size' => 10]);
        $section->addListItem('Pilotes de processus', 0, ['size' => 10]);
        $section->addListItem('Participants à la revue', 0, ['size' => 10]);
        $section->addListItem('Archivage SMI', 0, ['size' => 10]);
    }
}
