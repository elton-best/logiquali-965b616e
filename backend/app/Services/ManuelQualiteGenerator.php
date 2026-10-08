<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\Document;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class ManuelQualiteGenerator
{
    /**
     * Generate N2 - Manuel Qualité DOCX (ISO 9001:2015 clause 7.5)
     */
    public function generate(Enterprise $enterprise): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1200,
            'marginRight' => 1200,
            'marginTop' => 1200,
            'marginBottom' => 1200,
        ]);

        // Cover page
        $section->addText($enterprise->name, ['bold' => true, 'size' => 18, 'color' => '2E3B55'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(3);
        
        $section->addText('MANUEL QUALITÉ', ['bold' => true, 'size' => 24, 'color' => '2E3B55'], ['alignment' => Jc::CENTER]);
        $section->addText('Système de Management Intégré QHSE', ['size' => 16, 'color' => '666666'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(2);
        
        $section->addText('ISO 9001:2015 | ISO 14001:2015 | ISO 45001:2018', ['bold' => true, 'size' => 12, 'color' => 'FF6600'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(5);

        // Metadata table
        $metaTable = $section->addTable(['borderSize' => 8, 'borderColor' => '2E3B55']);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Code Document:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('MAN-SMI-001', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Version:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('1.0', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date d\'application:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText(now()->format('d/m/Y'), ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Pilote SMI:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('Responsable Qualité', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Niveau Pyramide:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('N2 - Manuel', ['bold' => true, 'size' => 11, 'color' => 'FF6600']);

        $section->addPageBreak();

        // TABLE OF CONTENTS (simplified)
        $this->addChapterTitle($section, 'SOMMAIRE');
        
        $tocStyle = ['size' => 11];
        $section->addText('1. PRÉSENTATION DE L\'ORGANISME', $tocStyle);
        $section->addText('2. DOMAINE D\'APPLICATION DU SMI', $tocStyle);
        $section->addText('3. CONTEXTE DE L\'ORGANISME', $tocStyle);
        $section->addText('4. LEADERSHIP ET ENGAGEMENT', $tocStyle);
        $section->addText('5. POLITIQUE QUALITÉ', $tocStyle);
        $section->addText('6. PLANIFICATION', $tocStyle);
        $section->addText('7. SUPPORT', $tocStyle);
        $section->addText('8. RÉALISATION DES ACTIVITÉS', $tocStyle);
        $section->addText('9. ÉVALUATION DES PERFORMANCES', $tocStyle);
        $section->addText('10. AMÉLIORATION', $tocStyle);
        
        $section->addPageBreak();

        // CHAPTER 1
        $this->addChapterTitle($section, '1. PRÉSENTATION DE L\'ORGANISME');
        
        $this->addSection($section, '1.1 Raison Sociale et Identité');
        $section->addText('Raison sociale: ' . $enterprise->name, ['size' => 11]);
        $section->addText('Adresse: ' . ($enterprise->address ?? '________'), ['size' => 11]);
        $section->addText('SIRET: ' . ($enterprise->siret ?? '________'), ['size' => 11]);
        $section->addTextBreak(1);

        $this->addSection($section, '1.2 Activités et Métiers');
        $section->addText(
            'L\'organisme opère dans les secteurs définis dans le domaine d\'application. Ses activités sont décrites dans la cartographie des processus (référence: CARTO-001).',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '1.3 Certifications et Reconnaissances');
        $section->addListItem('ISO 9001:2015 - Système de Management de la Qualité', 0, null, ['size' => 11]);
        $section->addListItem('ISO 14001:2015 - Système de Management Environnemental', 0, null, ['size' => 11]);
        $section->addListItem('ISO 45001:2018 - Système de Management de la Santé et Sécurité au Travail', 0, null, ['size' => 11]);
        $section->addTextBreak(2);

        // CHAPTER 2
        $this->addChapterTitle($section, '2. DOMAINE D\'APPLICATION DU SMI');
        
        $section->addText(
            'Le Système de Management Intégré s\'applique à l\'ensemble des activités, produits et services de l\'organisme sur tous les sites opérationnels.',
            ['size' => 11]
        );
        $section->addTextBreak(1);
        
        $section->addText('Périmètre géographique:', ['bold' => true, 'size' => 11]);
        $section->addText('Tous les sites de l\'entreprise (siège social + sites de production/service)', ['size' => 11]);
        $section->addTextBreak(1);
        
        $section->addText('Axes activés:', ['bold' => true, 'size' => 11]);
        $section->addListItem('Qualité (Q) - ISO 9001:2015', 0, null, ['size' => 11]);
        $section->addListItem('Hygiène & Sécurité (H/S) - ISO 45001:2018', 0, null, ['size' => 11]);
        $section->addListItem('Environnement (E) - ISO 14001:2015', 0, null, ['size' => 11]);
        $section->addTextBreak(2);

        // CHAPTER 3
        $this->addChapterTitle($section, '3. CONTEXTE DE L\'ORGANISME (ISO 9001 - Clause 4)');
        
        $this->addSection($section, '3.1 Enjeux Internes et Externes');
        $section->addText(
            'L\'analyse PESTEL et SWOT identifie les enjeux stratégiques impactant le SMI. Ces analyses sont documentées dans le registre de contexte (référence: M3-D1).',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '3.2 Parties Intéressées Pertinentes');
        $section->addText(
            'Les parties intéressées sont identifiées dans le registre des parties intéressées (M3-D2): Clients, Personnel, Actionnaires, Fournisseurs, Organismes réglementaires, Riverains.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '3.3 Cartographie des Processus');
        $section->addText(
            'Les processus sont classés en 3 catégories:',
            ['bold' => true, 'size' => 11]
        );
        $section->addListItem('Processus de Management (Pilotage, Stratégie, Revue Direction)', 0, null, ['size' => 11]);
        $section->addListItem('Processus Opérationnels (Réalisation, Production, Service)', 0, null, ['size' => 11]);
        $section->addListItem('Processus de Support (RH, IT, Achats, Maintenance)', 0, null, ['size' => 11]);
        $section->addTextBreak(2);

        // CHAPTER 4
        $this->addChapterTitle($section, '4. LEADERSHIP ET ENGAGEMENT (ISO 9001 - Clause 5)');
        
        $this->addSection($section, '4.1 Engagement de la Direction');
        $section->addText(
            'La Direction Générale démontre son leadership en:',
            ['size' => 11]
        );
        $section->addListItem('Définissant et communiquant la Politique Qualité (POL-QUA-001)', 0, null, ['size' => 11]);
        $section->addListItem('Fixant les objectifs stratégiques annuels', 0, null, ['size' => 11]);
        $section->addListItem('Allouant les ressources nécessaires au SMI', 0, null, ['size' => 11]);
        $section->addListItem('Présidant la Revue de Direction trimestrielle', 0, null, ['size' => 11]);
        $section->addTextBreak(1);

        $this->addSection($section, '4.2 Responsabilités et Autorités');
        $section->addText(
            'Les responsabilités SMI sont définies dans les fiches de poste et l\'organigramme. Le Responsable Qualité coordonne le système.',
            ['size' => 11]
        );
        $section->addTextBreak(2);

        // CHAPTER 5
        $this->addChapterTitle($section, '5. POLITIQUE QUALITÉ (ISO 9001 - Clause 5.2)');
        
        $section->addText(
            'La Politique Qualité est approuvée par la Direction et communiquée à tous les niveaux. Elle est disponible dans le document POL-QUA-001.',
            ['size' => 11]
        );
        $section->addTextBreak(1);
        
        $section->addText(
            '[La politique complète est annexée au présent manuel ou disponible séparément dans le document de référence POL-QUA-001]',
            ['size' => 10, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(2);

        // CHAPTER 6
        $this->addChapterTitle($section, '6. PLANIFICATION (ISO 9001 - Clause 6)');
        
        $this->addSection($section, '6.1 Actions face aux Risques et Opportunités');
        $section->addText(
            'Les risques et opportunités sont identifiés, évalués et traités selon la procédure PRD-RISQ-001. Le plan de maîtrise (M6-D1) est mis à jour trimestriellement.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '6.2 Objectifs Qualité et Plans d\'Actions');
        $section->addText(
            'Les objectifs SMART sont établis annuellement et déclinés par processus. Le tableau de bord (M10-D1) assure le suivi.',
            ['size' => 11]
        );
        $section->addTextBreak(2);

        // CHAPTER 7
        $this->addChapterTitle($section, '7. SUPPORT (ISO 9001 - Clause 7)');
        
        $this->addSection($section, '7.1 Ressources');
        $section->addListItem('Ressources humaines: Compétences définies par fiches de poste', 0, null, ['size' => 11]);
        $section->addListItem('Infrastructures: Bâtiments, équipements maintenus', 0, null, ['size' => 11]);
        $section->addListItem('Environnement de travail: Conditions SST conformes', 0, null, ['size' => 11]);
        $section->addTextBreak(1);

        $this->addSection($section, '7.2 Compétences et Sensibilisation');
        $section->addText(
            'Le plan de formation annuel assure la montée en compétence. Tout le personnel est sensibilisé au SMI lors de l\'intégration.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '7.3 Communication');
        $section->addText(
            'Communications internes: Intranet, réunions, affichages. Communications externes: Site web, rapports RSE.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        $this->addSection($section, '7.4 Informations Documentées');
        $section->addText(
            'La documentation est structurée en 5 niveaux (N1: Politique, N2: Manuel, N3: Procédures, N4: Instructions, N5: Enregistrements).',
            ['size' => 11]
        );
        $section->addTextBreak(2);

        // CHAPTER 8-10 (simplified for brevity)
        $this->addChapterTitle($section, '8. RÉALISATION DES ACTIVITÉS (ISO 9001 - Clause 8)');
        $section->addText('Les processus opérationnels sont détaillés dans les fiches processus (M4-D2) et procédures associées.', ['size' => 11]);
        $section->addTextBreak(2);

        $this->addChapterTitle($section, '9. ÉVALUATION DES PERFORMANCES (ISO 9001 - Clause 9)');
        $section->addText('Méthodes: Surveillance KPI, audits internes (PRD-AUD-001), enquêtes satisfaction, Revue de Direction (M12-D2).', ['size' => 11]);
        $section->addTextBreak(2);

        $this->addChapterTitle($section, '10. AMÉLIORATION (ISO 9001 - Clause 10)');
        $section->addText('L\'amélioration continue passe par: Traitement NC (M7-D1), actions correctives/préventives, innovation processus.', ['size' => 11]);
        $section->addTextBreak(3);

        // Footer
        $section->addText(
            '--- FIN DU MANUEL QUALITÉ ---',
            ['bold' => true, 'size' => 12, 'color' => '2E3B55'],
            ['alignment' => Jc::CENTER]
        );

        // Save
        $fileName = 'manuel_qualite_' . $enterprise->id . '_' . now()->format('Y-m-d') . '.docx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        return $filePath;
    }

    private function addChapterTitle($section, string $title): void
    {
        $section->addText($title, ['bold' => true, 'size' => 16, 'color' => '2E3B55']);
        $section->addTextBreak(1);
    }

    private function addSection($section, string $title): void
    {
        $section->addText($title, ['bold' => true, 'size' => 12, 'color' => '555555']);
        $section->addTextBreak(0.5);
    }
}
