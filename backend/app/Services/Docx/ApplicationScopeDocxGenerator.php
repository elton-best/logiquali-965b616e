<?php

namespace App\Services\Docx;

use App\Models\ApplicationScope;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ApplicationScopeDocxGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    public function generate(ApplicationScope $scope): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $enterprise = $scope->site?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // Titre
        $section->addText(
            'DOMAINE D\'APPLICATION DU SMQ',
            ['bold' => true, 'size' => 16, 'color' => '2E74B5'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
        );

        $siteName = $scope->site->name ?? 'NOM DE LA STRUCTURE';
        $section->addText(
            $siteName,
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 480]
        );

        // Version et date
        $section->addText(
            'Version: ' . ($scope->version ?? '1.0') . ' | Date: ' . now()->format('d/m/Y'),
            ['size' => 10, 'italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 240]
        );

        // Objet du document
        if ($scope->document_objective) {
            $this->addSection($section, '1. OBJET DU DOCUMENT', $scope->document_objective);
        }

        // Définition du domaine
        if ($scope->scope_definition) {
            $this->addSection($section, '2. DÉFINITION DU DOMAINE D\'APPLICATION', $scope->scope_definition);
        }

        // Documents référencés
        if (!empty($scope->referenced_documents)) {
            $section->addText('3. DOCUMENTS RÉFÉRENCÉS', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            foreach ($scope->referenced_documents as $index => $doc) {
                $section->addListItem($doc, 0, ['size' => 11], ['spaceAfter' => 60]);
            }
            $section->addTextBreak();
        }

        // Processus
        if (!empty($scope->processes)) {
            $section->addText('4. PROCESSUS DU SYSTÈME', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow();
            $table->addCell(6000)->addText('Processus', ['bold' => true]);
            $table->addCell(3000)->addText('Type', ['bold' => true]);
            
            foreach ($scope->processes as $process) {
                $table->addRow();
                $table->addCell(6000)->addText($process['name'] ?? '');
                $table->addCell(3000)->addText($this->getProcessTypeLabel($process['type'] ?? ''));
            }
            $section->addTextBreak();
        }

        // Produits et services
        if (!empty($scope->products_services)) {
            $section->addText('5. PRODUITS ET SERVICES', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            foreach ($scope->products_services as $item) {
                $section->addListItem($item, 0, ['size' => 11], ['spaceAfter' => 60]);
            }
            $section->addTextBreak();
        }

        // Unités organisationnelles
        if (!empty($scope->organizational_units)) {
            $section->addText('6. UNITÉS ORGANISATIONNELLES', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            foreach ($scope->organizational_units as $unit) {
                $section->addListItem($unit, 0, ['size' => 11], ['spaceAfter' => 60]);
            }
            $section->addTextBreak();
        }

        // Lieux
        if (!empty($scope->locations)) {
            $section->addText('7. LIEUX', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            foreach ($scope->locations as $location) {
                $section->addListItem($location, 0, ['size' => 11], ['spaceAfter' => 60]);
            }
            $section->addTextBreak();
        }

        // Exclusions
        if ($scope->scope_exclusions || $scope->iso_exclusions) {
            $section->addText('8. EXCLUSIONS', ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
            
            if ($scope->scope_exclusions) {
                $section->addText('Exclusions du domaine:', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
                $section->addText($scope->scope_exclusions, ['size' => 11], ['spaceAfter' => 120]);
            }
            
            if ($scope->iso_exclusions) {
                $section->addText('Exclusions ISO 9001:2015:', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
                $section->addText($scope->iso_exclusions, ['size' => 11], ['spaceAfter' => 60]);
                
                if ($scope->iso_exclusions_justification) {
                    $section->addText('Justification:', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
                    $section->addText($scope->iso_exclusions_justification, ['size' => 11]);
                }
            }
        }

        // Générer le fichier
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $outputPath = storage_path('app/temp/Domaine_Application_' . $scope->site_id . '_' . time() . '.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return $outputPath;
    }

    private function addSection($section, string $title, string $content)
    {
        $section->addText($title, ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);
        $section->addText($content, ['size' => 11], ['spaceAfter' => 240, 'alignment' => Jc::BOTH]);
    }

    private function getProcessTypeLabel(string $type): string
    {
        $labels = [
            'management' => 'Management',
            'pilotage' => 'Pilotage',
            'realization' => 'Réalisation',
            'operationnel' => 'Opérationnel',
            'support' => 'Support',
        ];

        return $labels[$type] ?? $type;
    }
}
