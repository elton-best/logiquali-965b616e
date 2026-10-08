<?php

namespace App\Modules\Leadership\Services;

use App\Models\Enterprise;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class PolitiqueQualiteGenerator
{
    /**
     * Generate N1 - Politique Qualité DOCX (ISO 9001:2015 clause 5.2)
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

        // Header - Logo/Company
        $headerTable = $section->addTable();
        $headerTable->addRow();
        $headerTable->addCell(8000)->addText($enterprise->name, ['bold' => true, 'size' => 16, 'color' => '2E3B55']);
        $headerTable->addCell(2000)->addText('N1', ['bold' => true, 'size' => 14, 'color' => 'FF6600'], ['alignment' => Jc::END]);
        
        $section->addTextBreak(1);

        // Title
        $titleStyle = ['bold' => true, 'size' => 20, 'color' => '2E3B55'];
        $section->addText('POLITIQUE QUALITÉ', $titleStyle, ['alignment' => Jc::CENTER]);
        $section->addText('Système de Management Intégré QHSE', ['size' => 14, 'color' => '666666'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(2);

        // Metadata
        $metaTable = $section->addTable(['borderSize' => 6, 'borderColor' => '2E3B55']);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Code:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('POL-QUA-001', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Version:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('1.0', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Date d\'approbation:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText(now()->format('d/m/Y'), ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Approuvée par:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('Direction Générale', ['size' => 11]);
        
        $metaTable->addRow();
        $metaTable->addCell(3000)->addText('Référence Norme:', ['bold' => true, 'size' => 11]);
        $metaTable->addCell(7000)->addText('ISO 9001:2015 (clause 5.2), ISO 14001:2015, ISO 45001:2018', ['size' => 11]);
        
        $section->addTextBreak(2);

        // 1. MESSAGE DE LA DIRECTION
        $this->addTitle($section, '1. MESSAGE DE LA DIRECTION');
        
        $section->addText(
            'La Direction Générale de ' . $enterprise->name . ' affirme son engagement plein et entier dans la mise en œuvre et le maintien d\'un Système de Management Intégré QHSE performant, conforme aux exigences des normes ISO 9001:2015, ISO 14001:2015 et ISO 45001:2018.',
            ['size' => 11]
        );
        $section->addTextBreak(1);
        
        $section->addText(
            'Cette politique constitue le cadre de référence pour l\'établissement et la revue de nos objectifs qualité, hygiène-sécurité et environnement.',
            ['size' => 11, 'italic' => true]
        );
        $section->addTextBreak(2);

        // 2. ENGAGEMENTS QUALITÉ
        $this->addTitle($section, '2. ENGAGEMENTS QUALITÉ (ISO 9001:2015)');
        
        $listStyle = ['size' => 11];
        $section->addListItem('Satisfaire pleinement les exigences de nos clients et accroître leur satisfaction', 0, null, $listStyle);
        $section->addListItem('Fournir des produits et services conformes aux standards de qualité les plus élevés', 0, null, $listStyle);
        $section->addListItem('Améliorer en continu l\'efficacité de nos processus', 0, null, $listStyle);
        $section->addListItem('Développer une culture qualité à tous les niveaux de l\'organisation', 0, null, $listStyle);
        $section->addListItem('Écouter activement nos parties intéressées (clients, personnel, fournisseurs)', 0, null, $listStyle);
        $section->addTextBreak(2);

        // 3. ENGAGEMENTS HYGIÈNE-SÉCURITÉ
        $this->addTitle($section, '3. ENGAGEMENTS HYGIÈNE-SÉCURITÉ (ISO 45001:2018)');
        
        $section->addListItem('Fournir des conditions de travail sûres et saines', 0, null, $listStyle);
        $section->addListItem('Éliminer les dangers et réduire les risques professionnels', 0, null, $listStyle);
        $section->addListItem('Garantir la consultation et la participation du personnel', 0, null, $listStyle);
        $section->addListItem('Se conformer aux exigences légales et réglementaires applicables', 0, null, $listStyle);
        $section->addListItem('Viser zéro accident et zéro maladie professionnelle', 0, null, $listStyle);
        $section->addTextBreak(2);

        // 4. ENGAGEMENTS ENVIRONNEMENT
        $this->addTitle($section, '4. ENGAGEMENTS ENVIRONNEMENT (ISO 14001:2015)');
        
        $section->addListItem('Protéger l\'environnement et prévenir la pollution', 0, null, $listStyle);
        $section->addListItem('Utiliser rationnellement les ressources naturelles et l\'énergie', 0, null, $listStyle);
        $section->addListItem('Réduire nos émissions, déchets et impacts environnementaux', 0, null, $listStyle);
        $section->addListItem('Respecter les obligations légales environnementales', 0, null, $listStyle);
        $section->addListItem('Impliquer nos partenaires dans une démarche éco-responsable', 0, null, $listStyle);
        $section->addTextBreak(2);

        // 5. AMÉLIORATION CONTINUE
        $this->addTitle($section, '5. AMÉLIORATION CONTINUE');
        
        $section->addText(
            'Nous nous engageons à:',
            ['bold' => true, 'size' => 11]
        );
        $section->addTextBreak(0.5);
        
        $section->addListItem('Fixer des objectifs SMART mesurables et revus annuellement', 0, null, $listStyle);
        $section->addListItem('Allouer les ressources nécessaires à l\'atteinte de ces objectifs', 0, null, $listStyle);
        $section->addListItem('Former et sensibiliser l\'ensemble du personnel au SMI', 0, null, $listStyle);
        $section->addListItem('Réaliser des audits internes et externes réguliers', 0, null, $listStyle);
        $section->addListItem('Traiter efficacement toute non-conformité, risque ou opportunité', 0, null, $listStyle);
        $section->addListItem('Communiquer cette politique à tous les niveaux de l\'organisation', 0, null, $listStyle);
        $section->addTextBreak(2);

        // 6. DIFFUSION ET RÉVISION
        $this->addTitle($section, '6. DIFFUSION ET RÉVISION');
        
        $section->addText(
            'Cette Politique Qualité est:',
            ['bold' => true, 'size' => 11]
        );
        $section->addTextBreak(0.5);
        
        $section->addListItem('Communiquée à l\'ensemble du personnel via l\'intranet et affichage', 0, null, $listStyle);
        $section->addListItem('Mise à disposition des parties intéressées sur demande', 0, null, $listStyle);
        $section->addListItem('Revue annuellement lors de la Revue de Direction', 0, null, $listStyle);
        $section->addListItem('Maintenue documentée et accessible en permanence', 0, null, $listStyle);
        $section->addTextBreak(3);

        // Signature
        $signTable = $section->addTable();
        
        $signTable->addRow();
        $signTable->addCell(5000)->addText('Fait à ' . ($enterprise->city ?? '________') . ', le ' . now()->format('d/m/Y'), ['size' => 11]);
        
        $signTable->addRow(1000);
        $signTable->addCell(5000)->addText('');
        
        $signTable->addRow();
        $signTable->addCell(5000)->addText('Signature Direction Générale', ['bold' => true, 'size' => 11]);
        
        $signTable->addRow(1500);
        $signTable->addCell(5000)->addText('(Signature & Cachet)', ['size' => 9, 'italic' => true, 'color' => '999999'], ['alignment' => Jc::CENTER]);

        // Save
        $fileName = 'politique_qualite_' . $enterprise->id . '_' . now()->format('Y-m-d') . '.docx';
        $filePath = storage_path('app/temp/' . $fileName);
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        return $filePath;
    }

    private function addTitle($section, string $title): void
    {
        $titleStyle = ['bold' => true, 'size' => 13, 'color' => '2E3B55'];
        $section->addText($title, $titleStyle);
        $section->addTextBreak(1);
    }
}
