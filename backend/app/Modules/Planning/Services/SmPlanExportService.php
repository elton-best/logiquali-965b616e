<?php

namespace App\Modules\Planning\Services;

use App\Modules\Planning\Models\SmPlanActivity;
use App\Modules\Enterprise\Models\Enterprise;
use App\Modules\Enterprise\Models\Site;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SmPlanExportService
{
    /**
     * Export SM Plan to Excel (identique au canevas officiel Fiche de Planification, Suivi et Évaluation)
     */
    public function exportSmPlanXlsx(int $enterpriseId, array $filters = []): string
    {
        $enterprise = Enterprise::find($enterpriseId);
        $year = (int) ($filters['year'] ?? date('Y'));
        $site = !empty($filters['site_id']) ? Site::find($filters['site_id']) : null;

        $query = SmPlanActivity::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('year', $year)
            ->with([
                'site',
                'process',
                'subActivities.actions.responsible',
                'subActivities.actions.rescheduler',
            ])
            ->orderBy('order');

        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        if (!empty($filters['process_id'])) {
            $query->where('process_id', $filters['process_id']);
        }

        $activities = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Plan SM {$year}");

        // Title Header
        $enterpriseName = $enterprise?->name ?? 'ENTREPRISE';
        $siteName = $site ? ' - Site : ' . $site->name : ' - Tous les sites';

        $sheet->setCellValue('A1', "PLAN DU SYSTÈME DE MANAGEMENT (SMQ / SMI) — ANNÉE {$year}");
        $sheet->setCellValue('A2', "Entreprise : {$enterpriseName}{$siteName} | Date d'extraction : " . date('d/m/Y H:i'));

        $sheet->mergeCells('A1:X1');
        $sheet->mergeCells('A2:X2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A'); // Blue 900
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Column Headers
        $headers = [
            'A4' => 'N° Activité',
            'B4' => 'Activité (Niveau 1)',
            'C4' => 'Sous-activité (Niveau 2)',
            'D4' => 'Action opérationnelle (Niveau 3)',
            'E4' => 'Responsable',
            'F4' => 'Acteurs Internes',
            'G4' => 'Acteurs Externes',
            'H4' => 'Livrables Attendus',
            'I4' => 'Indicateurs Associés',
            'J4' => 'Date Début',
            'K4' => 'Échéance',
            'L4' => 'Jan',
            'M4' => 'Fév',
            'N4' => 'Mar',
            'O4' => 'Avr',
            'P4' => 'Mai',
            'Q4' => 'Juin',
            'R4' => 'Juil',
            'S4' => 'Août',
            'T4' => 'Sept',
            'U4' => 'Oct',
            'V4' => 'Nov',
            'W4' => 'Déc',
            'X4' => 'Statut',
            'Y4' => 'Observations / Replanification',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A4:Y4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:Y4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB'); // Blue 600
        $sheet->getStyle('A4:Y4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Month Columns styling
        $sheet->getStyle('L4:W4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1D4ED8'); // Blue 700

        // Data Rows
        $row = 5;
        $monthCols = [
            1 => 'L', 2 => 'M', 3 => 'N', 4 => 'O', 5 => 'P', 6 => 'Q',
            7 => 'R', 8 => 'S', 9 => 'T', 10 => 'U', 11 => 'V', 12 => 'W'
        ];

        foreach ($activities as $act) {
            $actStartRow = $row;
            foreach ($act->subActivities as $sub) {
                $subStartRow = $row;
                foreach ($sub->actions as $action) {
                    $sheet->setCellValue("A{$row}", $act->code ?: $act->order);
                    $sheet->setCellValue("B{$row}", $act->title);
                    $sheet->setCellValue("C{$row}", $sub->title);
                    $sheet->setCellValue("D{$row}", $action->title);
                    $sheet->setCellValue("E{$row}", $action->responsible?->name ?: ($action->responsible_name ?: 'Non assigné'));
                    $sheet->setCellValue("F{$row}", $action->internal_actors ?: '—');
                    $sheet->setCellValue("G{$row}", $action->external_actors ?: '—');
                    $sheet->setCellValue("H{$row}", $action->deliverables ?: '—');
                    $sheet->setCellValue("I{$row}", $action->indicators ?: '—');
                    $sheet->setCellValue("J{$row}", $action->start_date ? $action->start_date->format('d/m/Y') : '—');
                    $sheet->setCellValue("K{$row}", $action->deadline ? $action->deadline->format('d/m/Y') : '—');

                    // Check months
                    $selectedMonths = is_array($action->months) ? $action->months : [];
                    foreach ($monthCols as $mNum => $colLetter) {
                        if (in_array($mNum, $selectedMonths)) {
                            $sheet->setCellValue("{$colLetter}{$row}", 'X');
                            $sheet->getStyle("{$colLetter}{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF16A34A'));
                            $sheet->getStyle("{$colLetter}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
                        } else {
                            $sheet->setCellValue("{$colLetter}{$row}", '');
                        }
                        $sheet->getStyle("{$colLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Statut & Replanification
                    $statusLabel = match ($action->status) {
                        'realise' => 'Réalisé',
                        'en_cours' => 'En cours',
                        'replanifie' => "Replanifié ({$action->rescheduled_count}x)",
                        'en_retard' => 'En retard',
                        default => 'À planifier',
                    };
                    $sheet->setCellValue("X{$row}", $statusLabel);

                    $obs = $action->observations ?? '';
                    if ($action->rescheduled_reason) {
                        $obs .= ($obs ? ' | ' : '') . "Motif report : {$action->rescheduled_reason}";
                    }
                    $sheet->setCellValue("Y{$row}", $obs ?: '—');

                    // Styling status
                    if ($action->status === 'realise') {
                        $sheet->getStyle("X{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF16A34A'))->setBold(true);
                    } elseif ($action->status === 'en_retard') {
                        $sheet->getStyle("X{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFDC2626'))->setBold(true);
                    } elseif ($action->status === 'replanifie') {
                        $sheet->getStyle("X{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD97706'))->setBold(true);
                    }

                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("X{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $row++;
                }

                // If subActivity had no actions, show a placeholder row
                if ($sub->actions->isEmpty()) {
                    $sheet->setCellValue("A{$row}", $act->code ?: $act->order);
                    $sheet->setCellValue("B{$row}", $act->title);
                    $sheet->setCellValue("C{$row}", $sub->title);
                    $sheet->setCellValue("D{$row}", '—');
                    $row++;
                }
            }

            // If activity had no subactivities
            if ($act->subActivities->isEmpty()) {
                $sheet->setCellValue("A{$row}", $act->code ?: $act->order);
                $sheet->setCellValue("B{$row}", $act->title);
                $sheet->setCellValue("C{$row}", '—');
                $row++;
            }
        }

        // Borders
        $lastRow = max(5, $row - 1);
        $sheet->getStyle("A4:Y{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        foreach ($monthCols as $col) {
            $sheet->getColumnDimension($col)->setWidth(5.5);
        }
        $sheet->getColumnDimension('X')->setAutoSize(true);
        $sheet->getColumnDimension('Y')->setWidth(30);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'sm_plan_') . '.xlsx';
        $writer->save($tempFile);

        return $tempFile;
    }
}

