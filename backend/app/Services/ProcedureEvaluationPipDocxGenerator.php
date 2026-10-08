<?php

namespace App\Services;

use App\Models\Enterprise;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class ProcedureEvaluationPipDocxGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    /**
     * Generate Performance Evaluation Procedure for Interested Parties (Personnel, Clients, Prestataires)
     */
    public function generate(?Enterprise $enterprise = null): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'marginTop' => 1000,
            'marginBottom' => 1000,
        ]);

        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // Title
        $titleStyle = ['bold' => true, 'size' => 18, 'color' => '2E3B55'];
        $section->addText('PROCÉDURE D\'ÉVALUATION DES PERFORMANCES', $titleStyle, ['alignment' => Jc::CENTER]);
        $section->addText('Personnel - Clients - Prestataires', ['bold' => true, 'size' => 14, 'color' => '2E3B55'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        // Metadata
        $metaTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Code:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('PRD-EVAL-PIP-001');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Version:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('1.0');
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date:', ['bold' => true]);
        $metaTable->addCell(7000)->addText(now()->format('d/m/Y'));
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Référence Normes:', ['bold' => true]);
        $metaTable->addCell(7000)->addText('ISO 9001:2015 (9.1.2), ISO 45001:2018, RGPD');
        
        $section->addTextBreak(2);

        // 1. OBJET
        $this->addSection($section, '1. OBJET');
        $section->addText(
            'Cette procédure définit les modalités d\'évaluation des performances et de la satisfaction des parties intéressées pertinentes: Personnel, Clients, et Prestataires externes. Elle assure le respect des exigences RGPD concernant les données personnelles.',
            ['size' => 11]
        );
        $section->addTextBreak(1);

        // 2. CHAMP D'APPLICATION
        $this->addSection($section, '2. CHAMP D\'APPLICATION');
        $section->addText(
            'Cette procédure s\'applique à:',
            ['size' => 11]
        );
        $listStyle = ['size' => 11];
        $section->addListItem('L\'ensemble du personnel de l\'organisme (évaluation annuelle des compétences et performances)', 0, null, $listStyle);
        $section->addListItem('Les clients (enquêtes de satisfaction post-prestation)', 0, null, $listStyle);
        $section->addListItem('Les prestataires et fournisseurs critiques (évaluation qualité des livraisons/services)', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 3. TERMES ET DÉFINITIONS
        $this->addSection($section, '3. TERMES ET DÉFINITIONS');
        
        $section->addListItem('PIP: Parties Intéressées Pertinentes (Personnel, Clients, Prestataires)', 0, null, $listStyle);
        $section->addListItem('NPS: Net Promoter Score (indicateur satisfaction client de -100 à +100)', 0, null, $listStyle);
        $section->addListItem('Anonymisation: Suppression irréversible des données personnelles identifiantes', 0, null, $listStyle);
        $section->addListItem('RGPD: Règlement Général sur la Protection des Données', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 4. ÉVALUATION PERSONNEL
        $this->addSection($section, '4. ÉVALUATION PERFORMANCE DU PERSONNEL');
        
        $this->addSubSection($section, '4.1 Fréquence et Modalités');
        $section->addText('Évaluation annuelle obligatoire pour tout le personnel + évaluation intermédiaire si nécessaire.', $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '4.2 Critères d\'Évaluation');
        $section->addListItem('Atteinte des objectifs individuels et collectifs', 0, null, $listStyle);
        $section->addListItem('Compétences techniques et comportementales', 0, null, $listStyle);
        $section->addListItem('Respect des procédures SMQ', 0, null, $listStyle);
        $section->addListItem('Contribution à l\'amélioration continue', 0, null, $listStyle);
        $section->addListItem('Formation et développement des compétences', 0, null, $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '4.3 Responsabilités');
        $section->addListItem('Manager direct: Réalise l\'entretien annuel', 0, null, $listStyle);
        $section->addListItem('RH: Centralise les fiches, assure l\'anonymisation RGPD après 3 ans', 0, null, $listStyle);
        $section->addListItem('Direction: Valide les plans de développement', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 5. ÉVALUATION CLIENTS
        $this->addSection($section, '5. ÉVALUATION SATISFACTION CLIENTS');
        
        $this->addSubSection($section, '5.1 Méthodes de Collecte');
        $section->addListItem('Enquêtes post-prestation (envoi J+7 après livraison/service)', 0, null, $listStyle);
        $section->addListItem('Questionnaires annuels pour clients récurrents', 0, null, $listStyle);
        $section->addListItem('Entretiens qualitatifs pour comptes clés', 0, null, $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '5.2 Indicateurs Mesurés');
        $section->addListItem('Taux de satisfaction globale (échelle 1-5)', 0, null, $listStyle);
        $section->addListItem('NPS (Net Promoter Score)', 0, null, $listStyle);
        $section->addListItem('Taux de réclamations', 0, null, $listStyle);
        $section->addListItem('Taux de fidélisation', 0, null, $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '5.3 Anonymisation RGPD');
        $section->addText(
            'Les données clients sont anonymisées après 12 mois pour les analyses statistiques. Les données nominatives sont conservées maximum 3 ans selon politique de conservation.',
            ['size' => 11, 'italic' => true]
        );
        $section->addTextBreak(1);

        // 6. ÉVALUATION PRESTATAIRES
        $this->addSection($section, '6. ÉVALUATION PERFORMANCE PRESTATAIRES');
        
        $this->addSubSection($section, '6.1 Périmètre');
        $section->addText('Tous les prestataires et fournisseurs impactant directement la qualité produit/service.', $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '6.2 Critères d\'Évaluation');
        $section->addListItem('Qualité des livraisons/prestations (taux de conformité)', 0, null, $listStyle);
        $section->addListItem('Respect des délais', 0, null, $listStyle);
        $section->addListItem('Réactivité et communication', 0, null, $listStyle);
        $section->addListItem('Gestion des non-conformités', 0, null, $listStyle);
        $section->addListItem('Certifications et conformité réglementaire', 0, null, $listStyle);
        $section->addTextBreak(0.5);

        $this->addSubSection($section, '6.3 Classification et Actions');
        
        $classTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $classTable->addRow();
        $classTable->addCell(2500)->addText('Note', ['bold' => true]);
        $classTable->addCell(2500)->addText('Classification', ['bold' => true]);
        $classTable->addCell(5000)->addText('Action', ['bold' => true]);

        $classTable->addRow();
        $classTable->addCell(2500)->addText('≥ 4/5', ['size' => 10]);
        $classTable->addCell(2500)->addText('Prestataire Excellent', ['size' => 10, 'color' => '00AA00']);
        $classTable->addCell(5000)->addText('Maintien collaboration, renouvellement contrat', ['size' => 10]);

        $classTable->addRow();
        $classTable->addCell(2500)->addText('3-4/5', ['size' => 10]);
        $classTable->addCell(2500)->addText('Prestataire Acceptable', ['size' => 10, 'color' => 'FF8800']);
        $classTable->addCell(5000)->addText('Points d\'amélioration identifiés, suivi renforcé', ['size' => 10]);

        $classTable->addRow();
        $classTable->addCell(2500)->addText('< 3/5', ['size' => 10]);
        $classTable->addCell(2500)->addText('Prestataire Non Conforme', ['size' => 10, 'color' => 'FF0000']);
        $classTable->addCell(5000)->addText('Plan d\'actions obligatoire ou déréférencement', ['size' => 10]);

        $section->addTextBreak(1);

        // 7. TRAITEMENT DES DONNÉES
        $this->addSection($section, '7. CONFORMITÉ RGPD - TRAITEMENT DES DONNÉES');
        
        $section->addListItem('Consentement explicite requis pour toute collecte de données personnelles', 0, null, $listStyle);
        $section->addListItem('Droit d\'accès, de rectification et de suppression respecté (délai 30 jours)', 0, null, $listStyle);
        $section->addListItem('Anonymisation automatique des données après période de conservation', 0, null, $listStyle);
        $section->addListItem('Chiffrement des données sensibles (notes personnelles, commentaires)', 0, null, $listStyle);
        $section->addListItem('Accès restreint aux données nominatives (RH, Direction uniquement)', 0, null, $listStyle);
        $section->addTextBreak(1);

        // 8. ENREGISTREMENTS
        $this->addSection($section, '8. ENREGISTREMENTS ET ARCHIVAGE');
        
        $archiveTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $archiveTable->addRow();
        $archiveTable->addCell(3500)->addText('Document', ['bold' => true]);
        $archiveTable->addCell(2500)->addText('Conservation', ['bold' => true]);
        $archiveTable->addCell(4000)->addText('Anonymisation', ['bold' => true]);

        $archiveTable->addRow();
        $archiveTable->addCell(3500)->addText('Fiches évaluation personnel', ['size' => 10]);
        $archiveTable->addCell(2500)->addText('3 ans', ['size' => 10]);
        $archiveTable->addCell(4000)->addText('Automatique après 3 ans', ['size' => 10]);

        $archiveTable->addRow();
        $archiveTable->addCell(3500)->addText('Enquêtes satisfaction clients', ['size' => 10]);
        $archiveTable->addCell(2500)->addText('1 an nominal', ['size' => 10]);
        $archiveTable->addCell(4000)->addText('Anonyme après 12 mois', ['size' => 10]);

        $archiveTable->addRow();
        $archiveTable->addCell(3500)->addText('Évaluations prestataires', ['size' => 10]);
        $archiveTable->addCell(2500)->addText('Durée contrat +2 ans', ['size' => 10]);
        $archiveTable->addCell(4000)->addText('Non applicable (données B2B)', ['size' => 10]);

        $section->addTextBreak(1);

        // 9. REVUE ET AMÉLIORATION
        $this->addSection($section, '9. REVUE DES RÉSULTATS ET ACTIONS D\'AMÉLIORATION');
        
        $section->addText(
            'Les résultats des évaluations sont revus trimestriellement en comité SMQ et annuellement en Revue de Direction. Les tendances négatives déclenchent des plans d\'actions correctifs.',
            $listStyle
        );

        // Save
        $fileName = 'procedure_evaluation_pip_' . now()->format('Y-m-d') . '.docx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        return $filePath;
    }

    private function addSection($section, string $title): void
    {
        $sectionStyle = ['bold' => true, 'size' => 14, 'color' => '2E3B55'];
        $section->addText($title, $sectionStyle);
        $section->addTextBreak(1);
    }

    private function addSubSection($section, string $title): void
    {
        $subSectionStyle = ['bold' => true, 'size' => 12, 'color' => '555555'];
        $section->addText($title, $subSectionStyle);
        $section->addTextBreak(0.5);
    }
}
