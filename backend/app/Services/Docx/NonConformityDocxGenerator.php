<?php

namespace App\Services\Docx;

use App\Models\Enterprise;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

/**
 * Générateur DOCX pour Procédure Non-Conformités (M7-D1)
 * 
 * Structure conforme ISO 9001:2015 clause 10.2:
 * - Objet de la procédure
 * - Champ d'application
 * - Termes et définitions
 * - Tableau: Activités, Sous-activités, Responsabilités, Livrables, Délai
 */
class NonConformityDocxGenerator
{
    private PhpWord $phpWord;
    private DocumentBrandingService $brandingService;
    
    public function __construct()
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Calibri');
        $this->phpWord->setDefaultFontSize(11);
        $this->brandingService = app(DocumentBrandingService::class);
    }
    
    /**
     * Génère la procédure NC (template générique)
     */
    public function generate(?Enterprise $enterprise = null): string
    {
        $section = $this->phpWord->addSection([
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }
        
        // En-tête
        $this->addHeader($section);
        
        // 1. Objet
        $this->addObjet($section);
        
        // 2. Champ d'application
        $this->addChamp($section);
        
        // 3. Termes et définitions
        $this->addTermes($section);
        
        $section->addPageBreak();
        
        // 4. Processus de traitement
        $this->addProcessTable($section);
        
        // 5. Annexes
        $this->addAnnexes($section);
        
        // Sauvegarde
        $filename = storage_path('app/temp/procedure_non_conformites_' . time() . '.docx');
        
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
    private function addHeader($section): void
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
            'PROCÉDURE DE GESTION DES NON-CONFORMITÉS',
            ['bold' => true, 'size' => 14, 'color' => '0066CC'],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(1);
        
        // Cartouche identification
        $infoTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
        
        $infoTable->addRow();
        $infoTable->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Code document', ['bold' => true]);
        $infoTable->addCell(7500)->addText('PRD_NC_001');
        
        $infoTable->addRow();
        $infoTable->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Version', ['bold' => true]);
        $infoTable->addCell(7500)->addText('1.0');
        
        $infoTable->addRow();
        $infoTable->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Date d\'application', ['bold' => true]);
        $infoTable->addCell(7500)->addText(now()->format('d/m/Y'));
        
        $infoTable->addRow();
        $infoTable->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Statut', ['bold' => true]);
        $infoTable->addCell(7500)->addText('Validé', ['color' => '00CC00', 'bold' => true]);
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 1: Objet
     */
    private function addObjet($section): void
    {
        $section->addText('1. OBJET DE LA PROCÉDURE', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $section->addText(
            'La présente procédure a pour objet de définir les modalités de traitement des non-conformités ' .
            'détectées dans le cadre du Système de Management Intégré (SMI), conformément aux exigences des normes ' .
            'ISO 9001:2015, ISO 14001:2015 et ISO 45001:2018.',
            ['size' => 11],
            ['alignment' => Jc::BOTH]
        );
        
        $section->addTextBreak();
        
        $section->addText('Elle vise à:', ['bold' => true]);
        $section->addListItem('Identifier et enregistrer les non-conformités', 0);
        $section->addListItem('Analyser les causes profondes', 0);
        $section->addListItem('Définir et mettre en œuvre des actions correctives', 0);
        $section->addListItem('Vérifier l\'efficacité des actions mises en place', 0);
        $section->addListItem('Prévenir la récurrence', 0);
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 2: Champ d'application
     */
    private function addChamp($section): void
    {
        $section->addText('2. CHAMP D\'APPLICATION', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $section->addText(
            'Cette procédure s\'applique à l\'ensemble des processus et activités de l\'organisme, ' .
            'ainsi qu\'à tous les sites et services concernés par le SMI.',
            ['size' => 11],
            ['alignment' => Jc::BOTH]
        );
        
        $section->addTextBreak();
        
        $section->addText('Types de non-conformités couvertes:', ['bold' => true]);
        $section->addListItem('Non-conformités produits/services', 0);
        $section->addListItem('Non-conformités processus', 0);
        $section->addListItem('Écarts par rapport aux exigences normatives', 0);
        $section->addListItem('Réclamations clients', 0);
        $section->addListItem('Incidents environnementaux', 0);
        $section->addListItem('Accidents/incidents de sécurité', 0);
        $section->addListItem('Constats d\'audits internes/externes', 0);
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 3: Termes et définitions
     */
    private function addTermes($section): void
    {
        $section->addText('3. TERMES ET DÉFINITIONS', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $termes = [
            'Non-conformité (NC)' => 'Non-satisfaction d\'une exigence (ISO 9000:2015, 3.6.9)',
            'Action corrective' => 'Action visant à éliminer la cause d\'une non-conformité et à éviter qu\'elle ne se reproduise',
            'Action préventive' => 'Action visant à éliminer la cause d\'une non-conformité potentielle',
            'Cause profonde' => 'Raison fondamentale à l\'origine de la non-conformité (méthode 5 Pourquoi / Ishikawa)',
            'Correction' => 'Action immédiate pour éliminer une non-conformité détectée (traitement symptomatique)',
            'Efficacité' => 'Niveau de réalisation des activités planifiées et d\'obtention des résultats escomptés',
            'Réclamation' => 'Expression d\'insatisfaction adressée à un organisme au sujet de ses produits/services',
        ];
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
        ]);
        
        $table->addRow();
        $table->addCell(3000, ['bgColor' => 'E7E6E6'])->addText('Terme', ['bold' => true]);
        $table->addCell(6500, ['bgColor' => 'E7E6E6'])->addText('Définition', ['bold' => true]);
        
        foreach ($termes as $terme => $definition) {
            $table->addRow();
            $table->addCell(3000)->addText($terme, ['bold' => true, 'size' => 10]);
            $table->addCell(6500)->addText($definition, ['size' => 10], ['alignment' => Jc::BOTH]);
        }
        
        $section->addTextBreak(1);
    }
    
    /**
     * Section 4: Processus de traitement (tableau)
     */
    private function addProcessTable($section): void
    {
        $section->addText('4. PROCESSUS DE TRAITEMENT DES NON-CONFORMITÉS', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);
        
        // Header
        $table->addRow(600);
        $table->addCell(2000, ['bgColor' => '0066CC'])->addText('ACTIVITÉS', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(2500, ['bgColor' => '0066CC'])->addText('SOUS-ACTIVITÉS', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => '0066CC'])->addText('RESPONSABILITÉS', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, ['bgColor' => '0066CC'])->addText('LIVRABLES', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => '0066CC'])->addText('DÉLAI', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        
        // Étape 1: Détection
        $this->addProcessRow($table, 
            '1. DÉTECTION ET SIGNALEMENT',
            "• Identifier la non-conformité\n• Remplir fiche NC\n• Notifier responsable qualité",
            'Tout personnel\nPilote processus',
            'Fiche NC\nNotification',
            'Immédiat'
        );
        
        // Étape 2: Analyse
        $this->addProcessRow($table,
            '2. ANALYSE INITIALE',
            "• Évaluer gravité et urgence\n• Décider correction immédiate\n• Affecter responsable traitement",
            'Responsable Qualité\nPilote processus',
            'Fiche NC complétée\nCorrection appliquée',
            '24h-48h'
        );
        
        // Étape 3: Correction
        $this->addProcessRow($table,
            '3. CORRECTION IMMÉDIATE',
            "• Traiter symptôme\n• Isoler produit NC si nécessaire\n• Informer parties concernées",
            'Responsable traitement',
            'Produit conforme\nCompte-rendu',
            'Variable selon NC'
        );
        
        // Étape 4: Analyse causes
        $this->addProcessRow($table,
            '4. ANALYSE CAUSES PROFONDES',
            "• Méthode 5 Pourquoi\n• Diagramme Ishikawa\n• Identifier causes racines",
            'Responsable traitement\nÉquipe pluridisciplinaire',
            'Rapport analyse\nCauses identifiées',
            '5 jours'
        );
        
        // Étape 5: Actions correctives
        $this->addProcessRow($table,
            '5. ACTIONS CORRECTIVES',
            "• Définir plan d'action\n• Valider avec pilote processus\n• Mettre en œuvre actions",
            'Responsable traitement\nPilote processus',
            'Plan d\'action\nPreuves mise en œuvre',
            '30 jours (variable)'
        );
        
        // Étape 6: Vérification
        $this->addProcessRow($table,
            '6. VÉRIFICATION EFFICACITÉ',
            "• Contrôler résultats\n• Évaluer efficacité\n• Décider clôture ou reprise",
            'Responsable Qualité\nAuditeur interne',
            'Rapport vérification\nDécision',
            '30-60 jours après actions'
        );
        
        // Étape 7: Clôture
        $this->addProcessRow($table,
            '7. CLÔTURE ET CAPITALISATION',
            "• Archiver fiche NC\n• Mettre à jour base connaissances\n• Partager retour expérience",
            'Responsable Qualité',
            'Fiche NC clôturée\nREX diffusé',
            '7 jours'
        );
        
        $section->addTextBreak(1);
    }
    
    /**
     * Helper pour ajouter ligne processus
     */
    private function addProcessRow($table, string $activite, string $sousActivites, string $responsabilites, string $livrables, string $delai): void
    {
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText($activite, ['bold' => true, 'size' => 9]);
        $table->addCell(2500)->addText($sousActivites, ['size' => 9]);
        $table->addCell(1500)->addText($responsabilites, ['size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2000)->addText($livrables, ['size' => 9]);
        $table->addCell(1500)->addText($delai, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
    }
    
    /**
     * Section 5: Annexes
     */
    private function addAnnexes($section): void
    {
        $section->addPageBreak();
        
        $section->addText('5. ANNEXES', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        // Annexe A: Fiche NC type
        $section->addText('Annexe A - Fiche Non-Conformité (formulaire vierge)', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        
        // En-tête fiche
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('N° NC', ['bold' => true]);
        $table->addCell(7500)->addText('[Auto-généré]', ['italic' => true]);
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Date détection', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Détectée par', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Processus concerné', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Type NC', ['bold' => true]);
        $table->addCell(7500)->addText('☐ Produit  ☐ Processus  ☐ Réclamation  ☐ Audit  ☐ Autre');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Gravité', ['bold' => true]);
        $table->addCell(7500)->addText('☐ Mineure  ☐ Majeure  ☐ Critique');
        
        $table->addRow(1200);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Description NC', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow(1200);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Causes identifiées', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow(1200);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Actions correctives', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Responsable', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Délai traitement', ['bold' => true]);
        $table->addCell(7500)->addText('');
        
        $section->addTextBreak();
        
        // Annexe B: Documents de référence
        $section->addText('Annexe B - Documents de référence', ['bold' => true, 'size' => 12]);
        $section->addTextBreak();
        
        $section->addListItem('ISO 9001:2015 - Clause 10.2: Non-conformité et actions correctives', 0);
        $section->addListItem('ISO 14001:2015 - Clause 10.2: Non-conformité et actions correctives', 0);
        $section->addListItem('ISO 45001:2018 - Clause 10.2: Incident, non-conformité et actions correctives', 0);
        $section->addListItem('Manuel Qualité', 0);
        $section->addListItem('Procédure Actions Correctives et Préventives', 0);
    }
}
