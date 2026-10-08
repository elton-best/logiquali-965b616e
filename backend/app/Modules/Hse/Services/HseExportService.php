<?php

namespace App\Modules\Hse\Services;

use App\Modules\Hse\Models\Duerp;
use App\Modules\Hse\Models\DuerpDanger;
use App\Modules\Hse\Models\EnvironmentalAspect;
use App\Modules\Enterprise\Models\Enterprise;
use App\Modules\Enterprise\Models\Site;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HseExportService
{
    /**
     * Export DUERP to Excel (identique au canevas officiel Document Unique d'Évaluation des Risques Professionnels)
     */
    public function exportDuerpXlsx(int $enterpriseId, ?int $siteId = null, ?int $duerpId = null): string
    {
        $enterprise = Enterprise::find($enterpriseId);
        $site = $siteId ? Site::find($siteId) : null;

        $query = DuerpDanger::query()
            ->whereHas('duerp', function ($q) use ($enterpriseId, $siteId, $duerpId) {
                $q->where('enterprise_id', $enterpriseId);
                if ($siteId) {
                    $q->where('site_id', $siteId);
                }
                if ($duerpId) {
                    $q->where('id', $duerpId);
                } else {
                    $q->where('is_current', true);
                }
            })
            ->with(['duerp.site', 'process', 'responsible']);

        $dangers = $query->orderBy('work_unit')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DUERP - ISO 45001');

        // Document Header
        $enterpriseName = $enterprise?->name ?? 'ENTREPRISE';
        $siteName = $site ? ' - Site : ' . $site->name : ' - Tous les sites';
        
        $sheet->setCellValue('A1', "DOCUMENT UNIQUE D'ÉVALUATION DES RISQUES PROFESSIONNELS (DUERP) — ISO 45001");
        $sheet->setCellValue('A2', "Entreprise : {$enterpriseName}{$siteName} | Date d'extraction : " . date('d/m/Y H:i'));

        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B'); // Slate 800
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Column Headers
        $headers = [
            'A4' => 'N°',
            'B4' => 'Unité de Travail (UT)',
            'C4' => 'Activité / Poste',
            'D4' => 'Famille de Danger (INRS)',
            'E4' => 'Situation Dangereuse',
            'F4' => 'Risques Identifiés / Dommages',
            'G4' => 'Gravité (G)',
            'H4' => 'Fréquence (F)',
            'I4' => 'Risque Brut (Score)',
            'J4' => 'Niveau Brut',
            'K4' => 'Mesures Existantes de Prévention',
            'L4' => 'Plan d\'Actions Préventives',
            'M4' => 'Responsable',
            'N4' => 'Délai / Échéance',
            'O4' => 'Statut Action',
            'P4' => 'Risque Résiduel (Score)',
            'Q4' => 'Niveau Résiduel',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A4:Q4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:Q4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E'); // Teal 700
        $sheet->getStyle('A4:Q4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Data Rows
        $row = 5;
        $idx = 1;
        foreach ($dangers as $danger) {
            $sheet->setCellValue("A{$row}", $idx++);
            $sheet->setCellValue("B{$row}", $danger->work_unit ?: ($danger->process?->title ?? 'N/A'));
            $sheet->setCellValue("C{$row}", $danger->activity ?? 'N/A');
            $sheet->setCellValue("D{$row}", $danger->inrs_family ?? 'N/A');
            $sheet->setCellValue("E{$row}", $danger->dangerous_situation ?? 'N/A');
            $sheet->setCellValue("F{$row}", $danger->identified_risks ?: ($danger->consequences ?? 'N/A'));
            $sheet->setCellValue("G{$row}", $danger->gravity ?? 1);
            $sheet->setCellValue("H{$row}", $danger->frequency ?? 1);
            $sheet->setCellValue("I{$row}", $danger->raw_risk_score ?? ($danger->gravity * $danger->frequency));
            $sheet->setCellValue("J{$row}", strtoupper((string) ($danger->raw_risk_level ?? 'Faible')));
            $sheet->setCellValue("K{$row}", $danger->existing_preventions ?: 'Aucune');
            $sheet->setCellValue("L{$row}", $danger->prevention_actions ?: 'Aucune');
            $sheet->setCellValue("M{$row}", $danger->responsible?->name ?: ($danger->responsible_name ?: 'Non assigné'));
            $sheet->setCellValue("N{$row}", $danger->deadline ? $danger->deadline->format('d/m/Y') : '—');
            $sheet->setCellValue("O{$row}", ucfirst(str_replace('_', ' ', (string) ($danger->action_status ?? 'en_cours'))));
            $sheet->setCellValue("P{$row}", $danger->residual_risk_score ?? '—');
            $sheet->setCellValue("Q{$row}", strtoupper((string) ($danger->residual_risk_level ?? '—')));

            // Center numbers and dates
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$row}:Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Conditional formatting color on Level
            $level = strtolower((string) $danger->raw_risk_level);
            if ($level === 'fort') {
                $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('FFDC2626'); // Red
                $sheet->getStyle("J{$row}")->getFont()->setBold(true);
            } elseif ($level === 'moyen') {
                $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('FFD97706'); // Amber
            }

            $row++;
        }

        // Borders
        $lastRow = max(5, $row - 1);
        $sheet->getStyle("A4:Q{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        // Auto-size columns
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'duerp_') . '.xlsx';
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Export AES to Excel (identique au canevas officiel BASE AES 2.xlsx)
     */
    public function exportEnvironmentalAspectsXlsx(int $enterpriseId, array $filters = []): string
    {
        $enterprise = Enterprise::find($enterpriseId);
        $site = !empty($filters['site_id']) ? Site::find($filters['site_id']) : null;

        $query = EnvironmentalAspect::query()
            ->where('enterprise_id', $enterpriseId)
            ->with(['site', 'process', 'responsible']);

        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        if (!empty($filters['process_id'])) {
            $query->where('process_id', $filters['process_id']);
        }
        if (isset($filters['is_significant'])) {
            $query->where('is_significant', (bool) $filters['is_significant']);
        }

        $aspects = $query->orderBy('process_id')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('AES - ISO 14001');

        // Header
        $enterpriseName = $enterprise?->name ?? 'ENTREPRISE';
        $siteName = $site ? ' - Site : ' . $site->name : ' - Tous les sites';

        $sheet->setCellValue('A1', "ANALYSE ENVIRONNEMENTALE & ASPECTS ENVIRONNEMENTAUX SIGNIFICATIFS (AES) — ISO 14001");
        $sheet->setCellValue('A2', "Entreprise : {$enterpriseName}{$siteName} | Seuil de significativité standard : 343 | Date d'extraction : " . date('d/m/Y H:i'));

        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534'); // Green 800
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Column Headers
        $headers = [
            'A4' => 'N°',
            'B4' => 'Processus / Domaine',
            'C4' => 'Sous-Processus / Zone',
            'D4' => 'Mode (N/A)',
            'E4' => 'Activité',
            'F4' => 'Aspect Environnemental',
            'G4' => 'Impact Environnemental',
            'H4' => 'Mesures Existantes de Maîtrise',
            'I4' => 'Gravité (G)',
            'J4' => 'Fréquence (F)',
            'K4' => 'Sensibilité (S)',
            'L4' => 'Maîtrise (M)',
            'M4' => 'Score Criticité (IC)',
            'N4' => 'Significatif ?',
            'O4' => 'Actions de Maîtrise Complémentaires',
            'P4' => 'Responsable',
            'Q4' => 'Délai / Échéance',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A4:Q4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:Q4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF15803D'); // Green 700
        $sheet->getStyle('A4:Q4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Rows
        $row = 5;
        $idx = 1;
        foreach ($aspects as $aspect) {
            $sheet->setCellValue("A{$row}", $idx++);
            $sheet->setCellValue("B{$row}", $aspect->process?->title ?? 'Général');
            $sheet->setCellValue("C{$row}", $aspect->sub_process ?? '—');
            $sheet->setCellValue("D{$row}", $aspect->mode ?? 'N');
            $sheet->setCellValue("E{$row}", $aspect->activity);
            $sheet->setCellValue("F{$row}", $aspect->aspect);
            $sheet->setCellValue("G{$row}", $aspect->impact);
            $sheet->setCellValue("H{$row}", $aspect->existing_controls ?: 'Aucune');
            $sheet->setCellValue("I{$row}", $aspect->gravity ?? 1);
            $sheet->setCellValue("J{$row}", $aspect->frequency ?? 1);
            $sheet->setCellValue("K{$row}", $aspect->sensitivity ?? 1);
            $sheet->setCellValue("L{$row}", $aspect->mastery ?? 1);
            $sheet->setCellValue("M{$row}", $aspect->criticality_score ?? ($aspect->gravity * $aspect->frequency * $aspect->sensitivity * $aspect->mastery));
            $sheet->setCellValue("N{$row}", $aspect->is_significant ? 'OUI (AES)' : 'NON');
            $sheet->setCellValue("O{$row}", $aspect->additional_actions ?: '—');
            $sheet->setCellValue("P{$row}", $aspect->responsible?->name ?: ($aspect->responsible_name ?: 'Non assigné'));
            $sheet->setCellValue("Q{$row}", $aspect->deadline ? $aspect->deadline->format('d/m/Y') : '—');

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight significant aspects
            if ($aspect->is_significant) {
                $sheet->getStyle("N{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFDC2626');
                $sheet->getStyle("M{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFDC2626');
                $sheet->getStyle("N{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
            }

            $row++;
        }

        // Borders
        $lastRow = max(5, $row - 1);
        $sheet->getStyle("A4:Q{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        // Auto-fit
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'aes_') . '.xlsx';
        $writer->save($tempFile);

        return $tempFile;
    }
}

