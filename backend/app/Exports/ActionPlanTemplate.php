<?php

namespace App\Exports;

use App\Models\Enterprise;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Export Action Plan Template (XLSX)
 */
class ActionPlanTemplate
{
    public function __construct(
        protected ?Enterprise $enterprise = null,
        protected ?DocumentBrandingService $documentBrandingService = null
    ) {
        $this->documentBrandingService ??= app(DocumentBrandingService::class);
    }

    /**
     * Generate template
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan d\'actions');
        
        // Headers
        $headers = [
            'N°', 'Titre', 'Description', 'Type', 'Priorité', 
            'Responsable (email)', 'Processus (code)', 'Codification documentaire', 'Délai (JJ/MM/AAAA)', 'Statut'
        ];
        
        foreach ($headers as $col => $header) {
            $cellCoord = chr(65 + $col) . '1';
            $sheet->setCellValue($cellCoord, $header);
        }
        
        // Style headers
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0066CC'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        
        // Example row
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Mettre à jour procédure NC');
        $sheet->setCellValue('C2', 'Révision complète de la procédure suite audit');
        $sheet->setCellValue('D2', 'Corrective');
        $sheet->setCellValue('E2', 'Haute');
        $sheet->setCellValue('F2', 'responsable@entreprise.com');
        $sheet->setCellValue('G2', 'PRC_NC_001');
        $sheet->setCellValue('H2', 'DOC-PRC-001');
        $sheet->setCellValue('I2', '31/12/2026');
        $sheet->setCellValue('J2', 'Planifiée');
        
        // Style example row
        $sheet->getStyle('A2:J2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEEEEE'],
            ],
            'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
        ]);
        
        // Legend
        $sheet->setCellValue('A4', 'LÉGENDE');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        $sheet->setCellValue('A6', 'Types:');
        $sheet->setCellValue('A7', '• Corrective');
        $sheet->setCellValue('A8', '• Preventive');
        $sheet->setCellValue('A9', '• Amelioration');
        
        $sheet->setCellValue('D6', 'Priorités:');
        $sheet->setCellValue('D7', '• Critique');
        $sheet->setCellValue('D8', '• Haute');
        $sheet->setCellValue('D9', '• Moyenne');
        $sheet->setCellValue('D10', '• Basse');
        
        $sheet->setCellValue('G6', 'Statuts:');
        $sheet->setCellValue('G7', '• Planifiée');
        $sheet->setCellValue('G8', '• En cours');
        $sheet->setCellValue('G9', '• Terminée');
        $sheet->setCellValue('G10', '• Validée');
        $sheet->setCellValue('G11', '• Annulée');
        
        // Column widths
        $widths = [5, 30, 40, 15, 12, 25, 18, 18, 18, 15];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension(chr(65 + $col))->setWidth($width);
        }
        
        // Save
        $filename = storage_path('app/temp/template_plan_actions_' . time() . '.xlsx');
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($this->enterprise) {
            $this->documentBrandingService?->applyXlsxBranding(
                $spreadsheet,
                $this->enterprise,
                "Template plan d'actions"
            );
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        
        return $filename;
    }
}
