<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class ProcedureRevueDirectionDocxGenerator
{
    /**
     * Generate Management Review Procedure DOCX (ISO 9001:2015 Clause 9.3)
     */
    public function generate(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'marginTop' => 1000,
            'marginBottom' => 1000,
        ]);

        // Title
        $titleStyle = ['bold' => true, 'size' => 18, 'color' => '2E3B55'];
        $section->addText('PROCÉDURE REVUE DE DIRECTION', $titleStyle, ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        // Metadata
        $metaTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Code:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('PRD-RDD-001');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Version:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('1.0');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date:', ['bold' => true]);
        $metaTable->addCell(7000)->addText(now()->format('d/m/Y'));
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Référence Norme:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('ISO 9001:2015 - Clause 9.3');
        
        $section->addTextBreak(2);

        // 1. OBJET
        $this->addSection($section, '1. OBJET');
        $section->addText(
            'Cette procédure définit les modalités de planification, de préparation, de réalisation et de suivi des revues de direction dans le cadre du Système de Management de la Qualité (SMQ) conforme à l\'ISO 9001:2015.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        // 2. CHAMP D'APPLICATION
        $this->addSection($section, '2. CHAMP D\'APPLICATION');
        $section->addText(
            'Cette procédure s\'applique à l\'ensemble des sites et processus de l\'organisme. Elle concerne la Direction, les responsables de processus, les pilotes SMQ et tous les participants aux revues de direction.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        // 3. TERMES ET DÉFINITIONS
        $this->addSection($section, '3. TERMES ET DÉFINITIONS');
        
        $listStyle = ['size' => 11];
        $section->addListItem('Revue de Direction: Évaluation par la direction de la pertinence, de l\'adéquation et de l\'efficacité du SMQ', 0, null, $listStyle);
        $section->addListItem('Données d\'entrée: Informations nécessaires pour réaliser la revue (performances, audits, réclamations, etc.)', 0, null, $listStyle);
        $section->addListItem('Données de sortie: Décisions et actions résultant de la revue', 0, null, $listStyle);
        $section->addListItem('Plan d\'actions: Ensemble des actions décidées suite à la revue', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 4. FRÉQUENCE
        $this->addSection($section, '4. FRÉQUENCE DES REVUES');
        $section->addText(
            'Les revues de direction doivent être réalisées au minimum une fois par an. Des revues exceptionnelles peuvent être organisées en cas de:',
            ['size' => 11]
        );
        $section->addListItem('Changements importants dans le contexte de l\'organisme', 0, null, $listStyle);
        $section->addListItem('Non-conformités majeures détectées', 0, null, $listStyle);
        $section->addListItem('Évolutions réglementaires ou normatives', 0, null, $listStyle);
        $section->addListItem('Demandes des parties intéressées', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 5. ACTIVITÉS
        $this->addSection($section, '5. PROCESSUS DE REVUE DE DIRECTION');
        
        $activitiesTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'width' => 100 * 50]);
        
        // Header
        $headerStyle = ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'];
        $headerBg = 'FF2E3B55';
        $activitiesTable->addRow(800);
        $activitiesTable->addCell(1500, ['bgColor' => $headerBg])->addText('ÉTAPE', $headerStyle);
        $activitiesTable->addCell(2500, ['bgColor' => $headerBg])->addText('ACTIVITÉ', $headerStyle);
        $activitiesTable->addCell(2000, ['bgColor' => $headerBg])->addText('RESPONSABLE', $headerStyle);
        $activitiesTable->addCell(2500, ['bgColor' => $headerBg])->addText('LIVRABLES', $headerStyle);
        $activitiesTable->addCell(1500, ['bgColor' => $headerBg])->addText('DÉLAI', $headerStyle);

        // Rows
        $cellStyle = ['size' => 10];
        
        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('1', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Planification annuelle', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Responsable SMQ', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Calendrier prévisionnel', $cellStyle);
        $activitiesTable->addCell(1500)->addText('Début année', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('2', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Collecte données d\'entrée', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Pilotes processus', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Synthèses par processus', $cellStyle);
        $activitiesTable->addCell(1500)->addText('J-30', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('3', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Préparation dossier revue', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Responsable SMQ', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Dossier consolidé', $cellStyle);
        $activitiesTable->addCell(1500)->addText('J-15', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('4', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Convocation participants', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Direction / SMQ', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Invitation + ordre du jour', $cellStyle);
        $activitiesTable->addCell(1500)->addText('J-10', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('5', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Réalisation de la revue', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Direction (président)', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Compte-rendu + décisions', $cellStyle);
        $activitiesTable->addCell(1500)->addText('Jour J', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('6', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Rédaction rapport', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Responsable SMQ', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Rapport de revue', $cellStyle);
        $activitiesTable->addCell(1500)->addText('J+7', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('7', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Diffusion et archivage', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Responsable SMQ', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Rapport validé diffusé', $cellStyle);
        $activitiesTable->addCell(1500)->addText('J+15', $cellStyle);

        $activitiesTable->addRow();
        $activitiesTable->addCell(1500)->addText('8', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Suivi plan d\'actions', $cellStyle);
        $activitiesTable->addCell(2000)->addText('Responsables actions', $cellStyle);
        $activitiesTable->addCell(2500)->addText('Avancement actions', $cellStyle);
        $activitiesTable->addCell(1500)->addText('Continu', $cellStyle);

        $section->addTextBreak(1);

        // 6. DONNÉES D'ENTRÉE
        $this->addSection($section, '6. DONNÉES D\'ENTRÉE (ISO 9001:2015 - 9.3.2)');
        
        $section->addListItem('État des actions des revues précédentes', 0, null, $listStyle);
        $section->addListItem('Changements externes et internes pertinents pour le SMQ', 0, null, $listStyle);
        $section->addListItem('Performances et efficacité du SMQ (indicateurs, objectifs)', 0, null, $listStyle);
        $section->addListItem('Adéquation des ressources', 0, null, $listStyle);
        $section->addListItem('Efficacité des actions pour traiter les risques et opportunités', 0, null, $listStyle);
        $section->addListItem('Opportunités d\'amélioration', 0, null, $listStyle);
        $section->addListItem('Satisfaction client et retours des parties intéressées', 0, null, $listStyle);
        $section->addListItem('Résultats des audits internes et externes', 0, null, $listStyle);
        $section->addListItem('Non-conformités et actions correctives', 0, null, $listStyle);
        $section->addListItem('Réclamations clients', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 7. DONNÉES DE SORTIE
        $this->addSection($section, '7. DONNÉES DE SORTIE (ISO 9001:2015 - 9.3.3)');
        
        $section->addListItem('Décisions relatives aux opportunités d\'amélioration', 0, null, $listStyle);
        $section->addListItem('Besoins de modification du SMQ', 0, null, $listStyle);
        $section->addListItem('Besoins en ressources', 0, null, $listStyle);
        $section->addListItem('Plan d\'actions avec responsables et échéances', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 8. ENREGISTREMENTS
        $this->addSection($section, '8. ENREGISTREMENTS');
        
        $section->addListItem('Ordre du jour et convocations', 0, null, $listStyle);
        $section->addListItem('Dossier de préparation (synthèses processus)', 0, null, $listStyle);
        $section->addListItem('Feuille de présence', 0, null, $listStyle);
        $section->addListItem('Compte-rendu et rapport de revue', 0, null, $listStyle);
        $section->addListItem('Plan d\'actions validé', 0, null, $listStyle);
        $section->addListItem('Preuves de suivi des actions', 0, null, $listStyle);

        // Save
        $fileName = 'procedure_revue_direction_' . now()->format('Y-m-d') . '.docx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        return $filePath;
    }

    /**
     * Add section title
     */
    private function addSection($section, string $title): void
    {
        $sectionStyle = ['bold' => true, 'size' => 14, 'color' => '2E3B55'];
        $section->addText($title, $sectionStyle);
        $section->addTextBreak(1);
    }
}
