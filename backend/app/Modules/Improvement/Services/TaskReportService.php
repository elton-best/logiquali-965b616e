<?php

namespace App\Modules\Improvement\Services;

use App\Models\Action;
use App\Models\Audit;
use App\Models\CalibrationPlan;
use App\Models\Communication;
use App\Models\ComplianceObligationAction;
use App\Models\Formation;
use App\Models\MaintenancePlan;
use App\Models\NonConformity;
use App\Models\Objective;
use App\Models\OperationalProjectActivity;
use App\Models\OperationalProjectTask;
use App\Models\PlanAction;
use App\Models\ProcessRiskOpportunity;
use App\Models\Risk;
use App\Models\StakeholderRequirementAction;
use App\Models\User;

use App\Models\TaskTracking;
use App\Models\TaskTrackingHistory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class TaskReportService
{
    /**
     * Generate .docx report for a task
     */
    public function generateReport($task, string $type, $user): string
    {
        // Get tracking data
        $tracking = TaskTracking::where([
            'user_id' => $user->id,
            'trackable_type' => $this->mapTypeToModel($type),
            'trackable_id' => $task->id,
        ])->first();

        // Get history
        $history = [];
        if ($tracking) {
            $history = TaskTrackingHistory::where('task_tracking_id', $tracking->id)
                ->orderBy('changed_at', 'asc')
                ->get()
                ->map(function ($entry) {
                    return [
                        'changed_at' => $entry->changed_at->format('d/m/Y H:i'),
                        'changed_by' => $entry->changedBy?->name ?? 'System',
                        'status' => "{$entry->status_old} → {$entry->status_new}",
                        'rate' => "{$entry->progress_rate_old}% → {$entry->progress_rate_new}%",
                        'notes' => $entry->notes,
                    ];
                })
                ->toArray();
        }

        // Create PhpWord document
        $phpWord = new PhpWord();

        // Set default font
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Add title
        $section = $phpWord->addSection();
        $section->addTitle('Rapport de Suivi de Tâche', 1);

        // Add metadata
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
        $table->addRow();
        $this->addTableCell($table, 'Titre:', $task->title ?? $task->name ?? 'N/A');

        $table->addRow();
        $responsibleId = $this->getResponsibleUserId($task, $type);
        $responsible = $responsibleId ? \App\Models\User::find($responsibleId)?->name : 'N/A';
        $this->addTableCell($table, 'Responsable:', $responsible);

        $table->addRow();
        $startDate = $task->start_date ?? $task->scheduled_date ?? null;
        $this->addTableCell($table, 'Date de début:', $startDate?->format('d/m/Y') ?? 'N/A');

        $table->addRow();
        $endDate = $task->end_date ?? $task->completion_date ?? null;
        $this->addTableCell($table, 'Date de fin prévue:', $endDate?->format('d/m/Y') ?? 'N/A');

        $table->addRow();
        $deadline = $task->deadline ?? $startDate;
        $this->addTableCell($table, 'Deadline:', $deadline?->format('d/m/Y') ?? 'N/A');

        $table->addRow();
        $status = $tracking?->status ?? 'non_demarre';
        $statusLabel = $this->getStatusLabel($status);
        $this->addTableCell($table, 'Statut:', $statusLabel);

        $table->addRow();
        $rate = $tracking?->progress_rate ?? 0;
        $this->addTableCell($table, 'Taux de progression:', "{$rate}%");

        $table->addRow();
        $notes = $tracking?->notes ?? 'Aucune note';
        $this->addTableCell($table, 'Notes:', $notes);

        // Add history section
        if (!empty($history)) {
            $section->addPageBreak();
            $section->addTitle('Historique de Suivi', 2);

            $historyTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
            $historyTable->addRow();
            $this->addTableHeader($historyTable, ['Date & Heure', 'Modifié par', 'Changement Statut', 'Changement Taux', 'Observations']);

            foreach ($history as $entry) {
                $historyTable->addRow();
                $this->addTableCell($historyTable, $entry['changed_at']);
                $this->addTableCell($historyTable, $entry['changed_by']);
                $this->addTableCell($historyTable, $entry['status']);
                $this->addTableCell($historyTable, $entry['rate']);
                $this->addTableCell($historyTable, $entry['notes'] ?? '-');
            }
        }

        // Generate filename
        $filename = "rapport-{$type}-{$task->id}-" . now()->format('YmdHis') . '.docx';
        $path = "task-reports/{$filename}";

        // Save to storage
        $phpWord->save(storage_path("app/{$path}"));

        return $path;
    }

    /**
     * Add table header row
     */
    private function addTableHeader($table, array $headers): void
    {
        foreach ($headers as $header) {
            $cell = $table->getLastRow()->addCell();
            $cell->addText($header, ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        }
    }

    /**
     * Add table cell
     */
    private function addTableCell($table, string $label, ?string $value = null): void
    {
        $row = $table->getLastRow();
        $cell1 = $row->addCell();
        $cell1->addText($label, ['bold' => true]);

        if ($value !== null) {
            $cell2 = $row->addCell();
            $cell2->addText($value);
        }
    }

    /**
     * Map type to model
     */
    private function mapTypeToModel(string $type): string
    {
        return match ($type) {
            'action' => \App\Models\Action::class,
            'audit' => \App\Models\Audit::class,
            'formation' => \App\Models\Formation::class,
            'communication' => \App\Models\Communication::class,
            'non_conformity' => \App\Models\NonConformity::class,
            'objective' => \App\Models\Objective::class,
            'risk' => \App\Models\Risk::class,
            'plan_action' => \App\Models\PlanAction::class,
            'maintenance_plan' => \App\Models\MaintenancePlan::class,
            'calibration_plan' => \App\Models\CalibrationPlan::class,
            'compliance_obligation_action' => \App\Models\ComplianceObligationAction::class,
            'stakeholder_requirement_action' => \App\Models\StakeholderRequirementAction::class,
            'process_risk_opportunity' => \App\Models\ProcessRiskOpportunity::class,
            'operational_project_activity' => \App\Models\OperationalProjectActivity::class,
            'operational_project_task' => \App\Models\OperationalProjectTask::class,
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };
    }

    /**
     * Get responsible user ID
     */
    private function getResponsibleUserId($task, string $type): ?int
    {
        return match ($type) {
            'action' => $task->responsible_id,
            'audit' => $task->assigned_to ?? $task->lead_auditor_id,
            'formation' => $task->organizer_user_id,
            'communication' => $task->organizer_user_id,
            'non_conformity' => $task->responsible_id,
            'objective' => $task->responsible_id,
            'risk' => $task->responsible_id,
            'plan_action' => $task->responsible_id,
            'maintenance_plan' => $task->responsible_user_id,
            'calibration_plan' => $task->responsible_user_id,
            'compliance_obligation_action' => $task->responsible_id,
            'stakeholder_requirement_action' => $task->responsible_id,
            'process_risk_opportunity' => $task->responsible_user_id,
            'operational_project_activity' => $task->responsible_user_id,
            'operational_project_task' => $task->assigned_to,
            default => null,
        };
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'non_demarre' => 'Non démarré',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            default => 'Inconnu',
        };
    }
}
