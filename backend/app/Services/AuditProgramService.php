<?php

namespace App\Services;

use App\Models\AuditProgram;
use App\Models\Process;
use App\Models\Risk;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuditProgramService
{
    public function __construct(
        protected DocumentBrandingService $documentBrandingService
    ) {
    }

    /**
     * Génère des suggestions d'audits basées sur l'analyse des risques
     */
    public function generateFromRiskAnalysis(
        AuditProgram $program,
        int $minCriticality = 8,
        bool $includeAllProcesses = false
    ): array {
        $siteId = $program->site_id;
        $suggestions = [];

        // 1. Processus à risque élevé
        $highRiskProcesses = Process::where('site_id', $siteId)
            ->whereHas('risks', function($q) use ($minCriticality) {
                $q->where('criticality', '>=', $minCriticality);
            })
            ->with(['risks' => function($q) use ($minCriticality) {
                $q->where('criticality', '>=', $minCriticality)
                    ->orderByDesc('criticality');
            }])
            ->get();

        foreach ($highRiskProcesses as $process) {
            $maxCriticality = $process->risks->max('criticality');
            $riskCount = $process->risks->count();

            $suggestions[] = [
                'process_id' => $process->id,
                'process_title' => $process->title,
                'reason' => "Processus à risque critique",
                'priority' => $this->calculatePriority($maxCriticality),
                'frequency' => $this->suggestFrequency($maxCriticality),
                'criticality' => $maxCriticality,
                'risk_count' => $riskCount,
                'type' => 'process'
            ];
        }

        // 2. Processus jamais audités
        $neverAuditedProcesses = Process::where('site_id', $siteId)
            ->whereDoesntHave('audits')
            ->get();

        foreach ($neverAuditedProcesses as $process) {
            if (!$includeAllProcesses && !$process->risks()->exists()) {
                continue;
            }

            $suggestions[] = [
                'process_id' => $process->id,
                'process_title' => $process->title,
                'reason' => "Jamais audité",
                'priority' => 2,
                'frequency' => 'annual',
                'type' => 'process'
            ];
        }

        // 3. Processus non audités depuis longtemps
        $oldAudits = Process::where('site_id', $siteId)
            ->whereHas('audits', function($q) {
                $q->where('actual_date', '<', now()->subMonths(12));
            })
            ->get();

        foreach ($oldAudits as $process) {
            $lastAudit = $process->audits()->latest('actual_date')->first();
            $monthsSince = now()->diffInMonths($lastAudit->actual_date);

            $suggestions[] = [
                'process_id' => $process->id,
                'process_title' => $process->title,
                'reason' => "Dernier audit il y a {$monthsSince} mois",
                'priority' => $monthsSince > 18 ? 1 : 2,
                'frequency' => 'annual',
                'last_audit_date' => $lastAudit->actual_date,
                'type' => 'process'
            ];
        }

        // Trier par priorité
        usort($suggestions, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $suggestions;
    }

    /**
     * Calcule priorité selon criticité risque
     */
    protected function calculatePriority(int $criticality): int
    {
        return match(true) {
            $criticality >= 12 => 1,  // Critique
            $criticality >= 8 => 2,   // Élevé
            $criticality >= 4 => 3,   // Moyen
            default => 4              // Faible
        };
    }

    /**
     * Suggère fréquence selon criticité
     */
    protected function suggestFrequency(int $criticality): string
    {
        return match(true) {
            $criticality >= 12 => 'quarterly',
            $criticality >= 8 => 'biannual',
            $criticality >= 4 => 'annual',
            default => 'biannual'
        };
    }

    /**
     * Export calendrier programme au format XLSX
     */
    public function exportCalendarXlsx(AuditProgram $program): string
    {
        $program->loadMissing(['site.enterprise', 'programManager', 'audits.auditedProcesses', 'audits.leadAuditor']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tête
        $sheet->setTitle("Programme {$program->year}");
        $sheet->setCellValue('A1', "PROGRAMME AUDITS INTERNES {$program->year}");
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ]);

        // Infos programme
        $row = 3;
        $sheet->setCellValue("A{$row}", "Site:");
        $sheet->setCellValue("B{$row}", $program->site->name);
        $row++;
        $sheet->setCellValue("A{$row}", "Responsable:");
        $sheet->setCellValue("B{$row}", $program->programManager->name ?? '');
        $row++;
        $sheet->setCellValue("A{$row}", "Objectifs:");
        $sheet->setCellValue("B{$row}", $program->objectives);
        $sheet->mergeCells("B{$row}:H{$row}");
        
        // Tableau audits
        $row += 2;
        $headers = ['Réf', 'Type', 'Titre', 'Date Prévue', 'Trimestre', 'Responsable', 'Processus', 'Statut'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            $col++;
        }

        // Données audits
        $row++;
        foreach ($program->audits as $audit) {
            $processes = $audit->auditedProcesses->pluck('title')->implode(', ');
            
            $sheet->setCellValue("A{$row}", $audit->ref);
            $sheet->setCellValue("B{$row}", ucfirst($audit->type));
            $sheet->setCellValue("C{$row}", $audit->title);
            $sheet->setCellValue("D{$row}", $audit->planned_date?->format('d/m/Y'));
            $sheet->setCellValue("E{$row}", "Q{$audit->quarter}");
            $sheet->setCellValue("F{$row}", $audit->leadAuditor?->name);
            $sheet->setCellValue("G{$row}", $processes);
            $sheet->setCellValue("H{$row}", ucfirst($audit->status));

            // Coloration selon statut
            $color = match($audit->status) {
                'completed' => '92D050',
                'in_progress' => 'FFD966',
                'planned' => 'FFFFFF',
                'cancelled' => 'FF6B6B',
                default => 'FFFFFF'
            };
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            $row++;
        }

        // Statistiques
        $row += 2;
        $sheet->setCellValue("A{$row}", "STATISTIQUES");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", "Audits planifiés:");
        $sheet->setCellValue("B{$row}", $program->planned_audits_count);
        $row++;
        $sheet->setCellValue("A{$row}", "Audits réalisés:");
        $sheet->setCellValue("B{$row}", $program->completed_audits_count);
        $row++;
        $sheet->setCellValue("A{$row}", "Taux de conformité moyen:");
        $sheet->setCellValue("B{$row}", "{$program->conformity_rate}%");

        // Ajuster largeurs colonnes
        foreach(range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $enterprise = $program->site?->enterprise;
        if ($enterprise) {
            $this->documentBrandingService->applyXlsxBranding(
                $spreadsheet,
                $enterprise,
                "Programme d'audits {$program->year}"
            );
        }

        // Enregistrer
        $siteCode = $program->site->code ?: ('site_' . $program->site_id);
        $filename = "programme_audits_{$program->year}_{$siteCode}.xlsx";
        $filepath = "exports/audit-programs/{$filename}";

        $writer = new Xlsx($spreadsheet);
        $fullPath = storage_path("app/{$filepath}");
        File::ensureDirectoryExists(dirname($fullPath));
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Top processus audités
     */
    public function getTopProcesses(AuditProgram $program, int $limit = 5): array
    {
        $processes = [];
        
        foreach ($program->audits as $audit) {
            foreach ($audit->auditedProcesses as $process) {
                if (!isset($processes[$process->id])) {
                    $processes[$process->id] = [
                        'id' => $process->id,
                        'title' => $process->title,
                        'audit_count' => 0,
                        'avg_conformity' => 0
                    ];
                }
                $processes[$process->id]['audit_count']++;
            }
        }

        usort($processes, fn($a, $b) => $b['audit_count'] <=> $a['audit_count']);
        
        return array_slice($processes, 0, $limit);
    }

    /**
     * Tendance conformité
     */
    public function getConformityTrend(AuditProgram $program): array
    {
        return $program->audits()
            ->whereNotNull('conformity_rate')
            ->orderBy('actual_date')
            ->get(['actual_date', 'conformity_rate', 'title'])
            ->map(function($audit) {
                return [
                    'date' => $audit->actual_date?->format('Y-m-d'),
                    'rate' => $audit->conformity_rate,
                    'title' => $audit->title
                ];
            })
            ->toArray();
    }
}
