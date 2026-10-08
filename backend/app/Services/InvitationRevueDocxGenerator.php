<?php

namespace App\Services;

use App\Models\ManagementReview;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class InvitationRevueDocxGenerator
{
    /**
     * Generate Management Review Invitation DOCX
     */
    public function generate(ManagementReview $review): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1500,
            'marginRight' => 1500,
            'marginTop' => 1500,
            'marginBottom' => 1500,
        ]);

        // Company header/logo space
        $headerStyle = ['bold' => true, 'size' => 16, 'color' => '2E3B55'];
        $section->addText('INVITATION', $headerStyle, ['alignment' => Jc::CENTER]);
        $section->addText('REVUE DE DIRECTION', $headerStyle, ['alignment' => Jc::CENTER]);
        $section->addTextBreak(2);

        // Reference
        $refStyle = ['bold' => true, 'size' => 11];
        $section->addText('Référence: ' . ($review->ref ?? 'RDD-' . now()->format('Y-m')), $refStyle);
        $section->addText('Date: ' . now()->format('d/m/Y'), ['size' => 11]);
        $section->addTextBreak(2);

        // Invitation body
        $bodyStyle = ['size' => 11, 'lineHeight' => 1.5];
        
        $section->addText('Madame, Monsieur,', $bodyStyle);
        $section->addTextBreak(1);

        $section->addText(
            'Vous êtes cordialement invité(e) à participer à la Revue de Direction de notre Système de Management de la Qualité, qui se tiendra selon les modalités suivantes:',
            $bodyStyle
        );
        $section->addTextBreak(1);

        // Meeting details table
        $detailsTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $detailsTable->addRow();
        $detailsTable->addCell(3500)->addText('Date:', ['bold' => true, 'size' => 11]);
        $detailsTable->addCell(6500)->addText(
            \Carbon\Carbon::parse($review->planned_date)->translatedFormat('l d F Y'),
            ['size' => 11]
        );

        $detailsTable->addRow();
        $detailsTable->addCell(3500)->addText('Horaire:', ['bold' => true, 'size' => 11]);
        $detailsTable->addCell(6500)->addText(
            $review->start_time ?? '09:00' . ' - ' . $review->end_time ?? '12:00',
            ['size' => 11]
        );

        $detailsTable->addRow();
        $detailsTable->addCell(3500)->addText('Lieu:', ['bold' => true, 'size' => 11]);
        $detailsTable->addCell(6500)->addText(
            $review->location ?? 'Salle de réunion - Siège',
            ['size' => 11]
        );

        $detailsTable->addRow();
        $detailsTable->addCell(3500)->addText('Président de séance:', ['bold' => true, 'size' => 11]);
        $detailsTable->addCell(6500)->addText(
            $review->chairman->name ?? 'Direction Générale',
            ['size' => 11]
        );

        $section->addTextBreak(2);

        // Agenda
        $section->addText('ORDRE DU JOUR', ['bold' => true, 'size' => 12, 'color' => '2E3B55']);
        $section->addTextBreak(1);

        $agendaItems = [
            '1. Ouverture de la séance et présentation des participants',
            '2. Rappel des objectifs de la revue de direction (ISO 9001:2015 - 9.3)',
            '3. Bilan des actions de la revue précédente',
            '4. Analyse du contexte et changements significatifs',
            '5. Performances et efficacité du SMQ',
            '   - Atteinte des objectifs qualité',
            '   - Indicateurs de performance',
            '   - Résultats des audits internes/externes',
            '6. Satisfaction client et retours parties intéressées',
            '7. Non-conformités, réclamations et actions correctives',
            '8. Management des risques et opportunités',
            '9. Adéquation des ressources',
            '10. Opportunités d\'amélioration identifiées',
            '11. Décisions et plan d\'actions',
            '12. Clôture et synthèse',
        ];

        foreach ($agendaItems as $item) {
            $section->addText($item, ['size' => 11]);
        }

        $section->addTextBreak(2);

        // Documents to bring
        $section->addText('DOCUMENTS À CONSULTER', ['bold' => true, 'size' => 12, 'color' => '2E3B55']);
        $section->addTextBreak(1);

        $section->addText(
            'Un dossier de préparation vous sera transmis au plus tard 5 jours avant la réunion. Il comprendra:',
            $bodyStyle
        );
        $section->addTextBreak(0.5);

        $docs = [
            'Synthèse des performances par processus',
            'Tableau de bord des indicateurs SMQ',
            'Rapports d\'audits (interne et externes)',
            'Bilan des réclamations clients',
            'État des non-conformités et actions correctives',
            'Analyse des risques et opportunités',
            'Propositions d\'amélioration',
        ];

        foreach ($docs as $doc) {
            $section->addListItem($doc, 0, null, ['size' => 11]);
        }

        $section->addTextBreak(2);

        // Confirmation
        $section->addText(
            'Merci de confirmer votre présence avant le ' . 
            \Carbon\Carbon::parse($review->planned_date)->subDays(5)->format('d/m/Y') . 
            ' auprès du Responsable SMQ.',
            ['size' => 11, 'bold' => true]
        );
        $section->addTextBreak(2);

        // Closing
        $section->addText(
            'Nous vous remercions par avance de votre participation active à cette revue stratégique.',
            $bodyStyle
        );
        $section->addTextBreak(2);

        // Signature
        $section->addText('Cordialement,', $bodyStyle);
        $section->addTextBreak(2);

        $section->addText(
            $review->chairman->name ?? 'La Direction',
            ['bold' => true, 'size' => 11]
        );
        $section->addText('Président de la Revue de Direction', ['size' => 10, 'italic' => true]);

        // Save
        $fileName = 'invitation_revue_' . $review->ref . '_' . now()->format('Y-m-d') . '.docx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        return $filePath;
    }
}
