<?php

namespace App\Services;

use App\Models\ProcessReview;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ProcessReviewReportService
{
    private const METRIC_LABELS = [
        'pip_total' => 'Nombre total de fiches PIP',
        'pip_completed' => 'Fiches PIP terminées',
        'risks_count' => 'Nombre de risques',
        'opportunities_count' => 'Nombre d’opportunités',
        'objectives_count' => 'Nombre d’objectifs',
        'objective_rate' => 'Taux moyen d’atteinte des objectifs',
        'non_conformities_count' => 'Nombre de non-conformités',
        'satisfaction_rate' => 'Taux moyen de satisfaction',
    ];

    public function buildPdf(ProcessReview $review): array
    {
        $process = $review->process;
        $identification = (array) ($review->identification ?? []);
        $sections = (array) ($review->sections ?? []);
        $metrics = $this->resolveMetrics($review);
        $incidentTraceability = $this->resolveIncidentTraceability($review);

        $html = view('pdf.process-review-report', [
            'review' => $review,
            'process' => $process,
            'identification' => $identification,
            'sections' => $sections,
            'metrics' => $this->normalizeMetricsForRendering($metrics),
            'incidentTraceability' => $incidentTraceability,
            'reviewDate' => $this->formatDate($review->review_date),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $filename = sprintf('Revue_Processus_%s_%s.pdf', $process?->code ?? $review->process_id, now()->format('Ymd_His'));

        return [
            'binary' => $pdf->output(),
            'filename' => $filename,
            'extension' => 'pdf',
        ];
    }

    public function buildDocx(ProcessReview $review): array
    {
        $process = $review->process;
        $identification = (array) ($review->identification ?? []);
        $sections = (array) ($review->sections ?? []);
        $metrics = $this->resolveMetrics($review);
        $incidentTraceability = $this->resolveIncidentTraceability($review);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $titleStyle = ['bold' => true, 'size' => 16];
        $headingStyle = ['bold' => true, 'size' => 12];
        $bodyStyle = ['size' => 10];

        $section->addText('Rapport de Revue Processus', $titleStyle, ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
        $section->addText(sprintf('Processus: %s (%s)', $process?->title ?? 'N/A', $process?->code ?? 'N/A'), $bodyStyle);
        $section->addText(sprintf('Date revue: %s', $this->formatDate($review->review_date)), $bodyStyle);
        $section->addTextBreak(1);

        $section->addText('1. Identification', $headingStyle);
        $this->addKeyValue($section, 'Responsable Qualité (RQ)', (string) ($identification['rq_name'] ?? 'N/A'));
        $this->addKeyValue($section, 'Période couverte', sprintf(
            '%s -> %s',
            (string) ($identification['coverage_start'] ?? 'N/A'),
            (string) ($identification['coverage_end'] ?? 'N/A')
        ));
        $this->addKeyValue($section, 'Heure début', (string) ($identification['started_at'] ?? 'N/A'));
        $this->addKeyValue($section, 'Heure fin', (string) ($identification['ended_at'] ?? 'N/A'));
        $section->addTextBreak(1);

        $section->addText('2. Sections métier', $headingStyle);
        $this->addSectionParagraph($section, 'Synthèse PIP', (string) ($sections['pip_summary'] ?? ''));
        $this->addSectionParagraph($section, 'Risques / Opportunités', (string) ($sections['risk_opportunity_summary'] ?? ''));
        $this->addSectionParagraph($section, 'Objectifs / Activités / Projets', (string) ($sections['objectives_projects_summary'] ?? ''));
        $this->addSectionParagraph($section, 'Conformité / NC / Satisfaction', (string) ($sections['compliance_nc_satisfaction_summary'] ?? ''));
        $this->addSectionParagraph($section, 'Leadership / DUERP', (string) ($sections['management_duerp_display'] ?? ''));
        $section->addTextBreak(1);

        if (!empty($metrics)) {
            $section->addText('3. Indicateurs de synthèse', $headingStyle);
            foreach ($this->normalizeMetricsForRendering($metrics) as $item) {
                $this->addKeyValue($section, $item['label'], $item['value']);
            }
        }

        $section->addTextBreak(1);
        $section->addText('4. Traçabilité incidents', $headingStyle);
        $this->addKeyValue($section, 'Incidents liés', (string) ($incidentTraceability['incidents_linked_count'] ?? 0));
        $this->addKeyValue($section, 'NC issues d’incidents', (string) ($incidentTraceability['non_conformities_linked_count'] ?? 0));
        $this->addKeyValue($section, 'Actions correctives liées', (string) ($incidentTraceability['linked_actions_count'] ?? 0));
        $this->addKeyValue($section, 'Actions liées en retard', (string) ($incidentTraceability['linked_actions_overdue_count'] ?? 0));

        $recentIncidents = $incidentTraceability['recent_incidents'] ?? [];
        if (is_array($recentIncidents) && !empty($recentIncidents)) {
            $section->addText('Incidents récents liés:', ['bold' => true, 'size' => 10], ['spaceAfter' => 80]);
            foreach ($recentIncidents as $incident) {
                $title = (string) ($incident['title'] ?? ('Incident #' . ($incident['id'] ?? 'N/A')));
                $status = (string) ($incident['status'] ?? 'N/A');
                $dueDate = (string) ($incident['due_date'] ?? 'N/A');
                $section->addText(sprintf('- %s | Statut: %s | Échéance: %s', $title, $status, $dueDate), ['size' => 10], ['spaceAfter' => 60]);
            }
        }

        $filename = sprintf('Revue_Processus_%s_%s.docx', $process?->code ?? $review->process_id, now()->format('Ymd_His'));
        $tempPath = storage_path('app/temp/' . $filename);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return [
            'path' => $tempPath,
            'filename' => $filename,
            'extension' => 'docx',
        ];
    }

    private function addSectionParagraph($section, string $label, string $content): void
    {
        $section->addText($label . ':', ['bold' => true, 'size' => 10], ['spaceAfter' => 80]);
        $section->addText($content !== '' ? $content : 'Non renseigné', ['size' => 10], ['spaceAfter' => 120]);
    }

    private function addKeyValue($section, string $key, string $value): void
    {
        $section->addText(sprintf('%s : %s', $key, $value !== '' ? $value : 'N/A'), ['size' => 10], ['spaceAfter' => 80]);
    }

    private function resolveMetrics(ProcessReview $review): array
    {
        $computed = $review->getAttribute('computed_metrics');
        if (is_array($computed) && !empty($computed)) {
            return $computed;
        }

        $snapshot = $review->metrics_snapshot;
        return is_array($snapshot) ? $snapshot : [];
    }

    private function normalizeMetricsForRendering(array $metrics): array
    {
        $renderable = [];

        foreach (self::METRIC_LABELS as $key => $label) {
            if (!array_key_exists($key, $metrics)) {
                continue;
            }

            $value = $metrics[$key];
            if (str_ends_with($key, '_rate')) {
                $renderable[] = ['label' => $label, 'value' => sprintf('%s%%', (string) $value)];
                continue;
            }

            $renderable[] = ['label' => $label, 'value' => (string) $value];
        }

        foreach ($metrics as $key => $value) {
            if (array_key_exists($key, self::METRIC_LABELS)) {
                continue;
            }
            $renderable[] = ['label' => (string) $key, 'value' => (string) $value];
        }

        return $renderable;
    }

    private function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        if (is_string($date) && trim($date) !== '') {
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('d/m/Y', $timestamp);
            }
        }

        return now()->format('d/m/Y');
    }

    private function resolveIncidentTraceability(ProcessReview $review): array
    {
        $result = [
            'incidents_linked_count' => 0,
            'non_conformities_linked_count' => 0,
            'linked_actions_count' => 0,
            'linked_actions_overdue_count' => 0,
            'recent_incidents' => [],
        ];

        $processId = (int) ($review->process_id ?? 0);
        $siteId = (int) ($review->site_id ?? ($review->process?->site_id ?? 0));

        try {
            if (Schema::hasTable('actions')) {
                $actionsQuery = DB::table('actions')
                    ->whereNull('deleted_at')
                    ->where('source_type', 'reclamation');

                if ($siteId > 0 && Schema::hasColumn('actions', 'site_id')) {
                    $actionsQuery->where('site_id', $siteId);
                }
                if ($processId > 0 && Schema::hasColumn('actions', 'process_id')) {
                    $actionsQuery->where('process_id', $processId);
                }

                $result['linked_actions_count'] = (int) (clone $actionsQuery)->count();
                $result['incidents_linked_count'] = (int) (clone $actionsQuery)
                    ->whereNotNull('source_id')
                    ->distinct('source_id')
                    ->count('source_id');

                if (Schema::hasColumn('actions', 'deadline') && Schema::hasColumn('actions', 'status')) {
                    $result['linked_actions_overdue_count'] = (int) (clone $actionsQuery)
                        ->whereNotNull('deadline')
                        ->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])
                        ->where('deadline', '<', now()->toDateString())
                        ->count();
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('non_conformities')) {
                $ncQuery = DB::table('non_conformities')
                    ->whereNull('deleted_at')
                    ->where('source', 'complaint');

                if ($siteId > 0 && Schema::hasColumn('non_conformities', 'site_id')) {
                    $ncQuery->where('site_id', $siteId);
                }
                if ($processId > 0 && Schema::hasColumn('non_conformities', 'process_id')) {
                    $ncQuery->where('process_id', $processId);
                }

                $result['non_conformities_linked_count'] = (int) $ncQuery->count();
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('reclamations') && Schema::hasTable('actions')) {
                $incidentQuery = DB::table('reclamations')
                    ->join('actions', function ($join): void {
                        $join->on('actions.source_id', '=', 'reclamations.id')
                            ->where('actions.source_type', '=', 'reclamation');
                    })
                    ->whereNull('reclamations.deleted_at')
                    ->whereNull('actions.deleted_at');

                if ($siteId > 0 && Schema::hasColumn('reclamations', 'site_id')) {
                    $incidentQuery->where('reclamations.site_id', $siteId);
                }
                if ($processId > 0 && Schema::hasColumn('actions', 'process_id')) {
                    $incidentQuery->where('actions.process_id', $processId);
                }

                $result['recent_incidents'] = $incidentQuery
                    ->orderByDesc('actions.id')
                    ->limit(5)
                    ->get([
                        'reclamations.id',
                        'reclamations.title',
                        'reclamations.status',
                        'reclamations.due_date',
                    ])
                    ->map(fn ($row) => [
                        'id' => (int) data_get($row, 'id'),
                        'title' => (string) (data_get($row, 'title') ?: ''),
                        'status' => (string) (data_get($row, 'status') ?: ''),
                        'due_date' => $this->formatDate(data_get($row, 'due_date')),
                    ])
                    ->values()
                    ->all();
            }
        } catch (\Throwable) {
            // best effort
        }

        return $result;
    }
}
