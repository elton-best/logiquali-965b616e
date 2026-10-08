<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        if ((string) $request->input('type') === 'training') {
            return response()->json([
                'message' => 'Le type de plan "training" est déprécié. Utilisez /api/v1/training-plans.',
            ], 410);
        }

        $query = Plan::query()
            ->with('site')
            ->where('type', '!=', 'training');

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $perPage = max(1, min((int) $request->integer('per_page', 50), 300));
        $plans = $query->orderByDesc('year')->orderByDesc('id')->paginate($perPage);

        return PlanResource::collection($plans);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlanPayload($request);

        $user = $request->user();
        $isEnterpriseAdmin = $user && method_exists($user, 'isEnterpriseAdmin') && $user->isEnterpriseAdmin();
        $validated['status'] = $isEnterpriseAdmin ? 'validated' : 'in_progress';
        $validated['content'] = $this->normalizePlanContent(
            (string) $validated['type'],
            $validated['content'] ?? null,
        );

        $plan = Plan::create($validated);
        return new PlanResource($plan->load('site'));
    }

    public function show(Plan $plan)
    {
        if ($plan->type === 'training') {
            return response()->json([
                'message' => 'Le type de plan "training" est déprécié. Utilisez /api/v1/training-plans.',
            ], 410);
        }

        return new PlanResource($plan->load('site'));
    }

    public function update(Request $request, Plan $plan)
    {
        if ($plan->type === 'training') {
            return response()->json([
                'message' => 'Le type de plan "training" est déprécié. Utilisez /api/v1/training-plans.',
            ], 410);
        }

        $validated = $this->validatePlanPayload($request, true);
        $validatedType = (string) ($validated['type'] ?? $plan->type);
        if (array_key_exists('content', $validated)) {
            $validated['content'] = $this->normalizePlanContent(
                $validatedType,
                $validated['content'],
            );
        }

        $plan->update($validated);
        return new PlanResource($plan->load('site'));
    }

    public function destroy(Plan $plan)
    {
        if ($plan->type === 'training') {
            return response()->json([
                'message' => 'Le type de plan "training" est déprécié. Utilisez /api/v1/training-plans.',
            ], 410);
        }

        $plan->delete();
        return response()->json(null, 204);
    }

    public function exportXlsx(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => 'nullable|in:smq,audit,maintenance,communication',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $type = (string) ($validated['type'] ?? 'smq');
        $year = isset($validated['year']) ? (int) $validated['year'] : null;
        $siteId = (int) $validated['site_id'];

        $query = Plan::query()
            ->with('site')
            ->where('site_id', $siteId)
            ->where('type', $type);

        if ($year !== null) {
            $query->where('year', $year);
        }

        $plan = $query->orderByDesc('year')->orderByDesc('id')->first();

        if (!$plan) {
            return response()->json([
                'message' => 'Aucun plan trouvé pour les critères fournis.',
            ], 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plans du SM');

        $sheet->setCellValue('A1', 'FICHE M11-D1 - PLANS DU SM');
        $sheet->setCellValue('A2', 'Site : ' . (string) ($plan->site?->name ?? 'N/A'));
        $sheet->setCellValue('A3', 'Année : ' . (string) $plan->year);
        $sheet->setCellValue('D3', 'Statut : ' . $this->statusLabel((string) $plan->status));
        $sheet->setCellValue('A5', 'Note :');
        $sheet->setCellValue('B5', (string) data_get($plan->content, 'note', ''));

        $headers = [
            'Activité et sous activité',
            'Janv',
            'Fev',
            'Mar',
            'Avr',
            'Mai',
            'Juin',
            'Juil',
            'Août',
            'Sept',
            'Oct',
            'Nov',
            'Dec',
            'Suivi',
            'Observations',
        ];

        $headerRow = 7;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $sheet->getStyle("A{$headerRow}:O{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:O{$headerRow}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFE9EEF5');

        $monthKeys = ['jan', 'fev', 'mar', 'avr', 'mai', 'juin', 'juil', 'aout', 'sept', 'oct', 'nov', 'dec'];
        $activities = $this->normalizeActivities(data_get($plan->content, 'activities', []));
        $startRow = $headerRow + 1;
        $row = $startRow;

        foreach ($activities as $activity) {
            $activityLabel = trim((string) ($activity['label'] ?? ''));
            $activityMeta = [];
            if (!empty($activity['start_date'])) {
                $activityMeta[] = 'Début: ' . $activity['start_date'];
            }
            if (!empty($activity['planned_days'])) {
                $activityMeta[] = 'Durée: ' . $activity['planned_days'] . ' j';
            }
            if (!empty($activity['end_date'])) {
                $activityMeta[] = 'Fin: ' . $activity['end_date'];
            }
            if (!empty($activity['status'])) {
                $activityMeta[] = 'Statut: ' . $this->activityStatusLabel((string) $activity['status']);
            }

            $activityLine = 'ACTIVITÉ : ' . $activityLabel;
            if ($activityMeta !== []) {
                $activityLine .= ' | ' . implode(' | ', $activityMeta);
            }

            $sheet->setCellValue("A{$row}", $activityLine);
            $sheet->mergeCells("A{$row}:O{$row}");
            $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:O{$row}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFF5F8FF');
            $row++;

            $subActivities = is_array($activity['sub_activities'] ?? null)
                ? $activity['sub_activities']
                : [];

            foreach ($subActivities as $subActivity) {
                $sheet->setCellValue("A{$row}", (string) ($subActivity['label'] ?? ''));

                $months = (array) ($subActivity['months'] ?? []);
                foreach ($monthKeys as $index => $monthKey) {
                    $column = chr(ord('B') + $index);
                    $cell = "{$column}{$row}";
                    $isCovered = !empty($months[$monthKey]);
                    $sheet->setCellValue($cell, '');

                    if ($isCovered) {
                        $sheet->getStyle($cell)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB('FFC6EFCE');
                    }
                }

                $sheet->setCellValue("N{$row}", $this->rowProgressLabel((string) ($subActivity['progress'] ?? '')));
                $sheet->setCellValue("O{$row}", (string) ($subActivity['observation'] ?? ''));
                $row++;
            }
        }

        if ($row === $startRow) {
            $sheet->setCellValue("A{$row}", 'Aucune activité enregistrée.');
            $sheet->mergeCells("A{$row}:O{$row}");
            $row++;
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A{$headerRow}:O{$lastRow}")
            ->getAlignment()
            ->setWrapText(true);

        // Harmonize month columns for chronogram readability.
        foreach (range('B', 'M') as $monthColumn) {
            $sheet->getColumnDimension($monthColumn)->setWidth(8);
            $sheet->getStyle("{$monthColumn}{$headerRow}:{$monthColumn}{$lastRow}")
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }

        // Keep non-month columns readable while preserving a stable layout.
        $sheet->getColumnDimension('A')->setWidth(58);
        $sheet->getColumnDimension('N')->setWidth(16);
        $sheet->getColumnDimension('O')->setWidth(42);

        $filename = sprintf(
            'plans_du_sm_site_%d_%d_%s.xlsx',
            $siteId,
            (int) $plan->year,
            now()->format('Ymd_His')
        );

        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => null,
            'process_code' => 'GEN',
            'document_kind' => 'sm_plan_export_xlsx',
            'title' => 'Plans du SM - ' . strtoupper($type) . ' - ' . (int) $plan->year,
            'description' => 'Export XLSX du plan du système de management.',
            'file_source_path' => $tempPath,
            'file_extension' => 'xlsx',
            'created_by' => $request->user()?->id,
            'type' => 'ENR',
        ]);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function normalizeActivities(mixed $rawActivities): array
    {
        if (!is_array($rawActivities)) {
            return [];
        }

        $normalized = [];
        foreach ($rawActivities as $item) {
            if (!is_array($item)) {
                continue;
            }

            $subActivities = [];
            if (is_array($item['subActivities'] ?? null)) {
                foreach ($item['subActivities'] as $subActivity) {
                    if (!is_array($subActivity)) {
                        continue;
                    }
                    $subActivities[] = [
                        'label' => (string) ($subActivity['label'] ?? ''),
                        'months' => is_array($subActivity['months'] ?? null) ? $subActivity['months'] : [],
                        'progress' => (string) ($subActivity['progress'] ?? ''),
                        'observation' => (string) ($subActivity['observation'] ?? ''),
                    ];
                }
            }

            // Backward compatibility with legacy flat activity format.
            if (empty($subActivities)) {
                $subActivities[] = [
                    'label' => (string) ($item['label'] ?? ''),
                    'months' => is_array($item['months'] ?? null) ? $item['months'] : [],
                    'progress' => (string) ($item['progress'] ?? ''),
                    'observation' => (string) ($item['observation'] ?? ''),
                ];
            }

            $normalized[] = [
                'label' => (string) ($item['label'] ?? ''),
                'start_date' => $this->normalizeDateString($item['start_date'] ?? null),
                'planned_days' => $this->normalizePositiveInteger($item['planned_days'] ?? null, 5),
                'end_date' => $this->resolveEndDate(
                    $item['start_date'] ?? null,
                    $item['planned_days'] ?? null,
                    $item['end_date'] ?? null,
                ),
                'status' => $this->normalizeActivityStatus($item['status'] ?? null),
                'progress' => $this->normalizeActivityProgress(
                    $item['status'] ?? null,
                    $item['progress'] ?? null,
                ),
                'responsible' => (string) ($item['responsible'] ?? ''),
                'contributors' => (string) ($item['contributors'] ?? ''),
                'report' => (string) ($item['report'] ?? ''),
                'sub_activities' => $subActivities,
            ];
        }

        return $normalized;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'validated' => 'Validé',
            'in_progress' => 'En cours de validation',
            'completed' => 'Terminé',
            'archived' => 'Archivé',
            default => 'Brouillon',
        };
    }

    private function rowProgressLabel(string $progress): string
    {
        return match ($progress) {
            'en_cours' => 'En cours',
            'fait' => 'Fait',
            default => 'Non démarré',
        };
    }

    private function validatePlanPayload(Request $request, bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'site_id' => [$prefix, 'exists:sites,id'],
            'type' => [$prefix, 'in:smq,audit,maintenance,communication'],
            'title' => [$prefix, 'string', 'max:255'],
            'year' => [$prefix, 'integer', 'min:2020', 'max:2100'],
            'content' => ['nullable', 'array'],
            'content.note' => ['nullable', 'string'],
            'content.format_plan' => ['nullable', 'in:jour,semaine,mois'],
            'content.display_mode' => ['nullable', 'in:court,liste'],
            'content.activities' => ['nullable', 'array'],
            'content.activities.*.label' => ['required_with:content.activities', 'string', 'max:255'],
            'content.activities.*.start_date' => ['nullable', 'date'],
            'content.activities.*.planned_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'content.activities.*.end_date' => ['nullable', 'date'],
            'content.activities.*.status' => ['nullable', 'in:not_started,in_progress,completed'],
            'content.activities.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'content.activities.*.responsible' => ['nullable', 'string', 'max:255'],
            'content.activities.*.contributors' => ['nullable', 'string', 'max:500'],
            'content.activities.*.report' => ['nullable', 'string', 'max:2000'],
            'content.activities.*.subActivities' => ['nullable', 'array'],
            'content.activities.*.subActivities.*.label' => ['required_with:content.activities.*.subActivities', 'string', 'max:255'],
            'content.activities.*.subActivities.*.months' => ['nullable', 'array'],
            'content.activities.*.subActivities.*.progress' => ['nullable', 'in:,en_cours,fait'],
            'content.activities.*.subActivities.*.observation' => ['nullable', 'string', 'max:1000'],
            'file_path' => 'nullable|string',
            'status' => [$partial ? 'sometimes' : 'nullable', 'in:draft,validated,in_progress,completed,archived'],
        ]);
    }

    private function normalizePlanContent(string $type, mixed $content): ?array
    {
        if ($type !== 'smq') {
            return is_array($content) ? $content : null;
        }

        $contentArray = is_array($content) ? $content : [];

        return [
            'note' => (string) ($contentArray['note'] ?? ''),
            'format_plan' => in_array(($contentArray['format_plan'] ?? null), ['jour', 'semaine', 'mois'], true)
                ? $contentArray['format_plan']
                : 'jour',
            'display_mode' => in_array(($contentArray['display_mode'] ?? null), ['court', 'liste'], true)
                ? $contentArray['display_mode']
                : 'liste',
            'activities' => $this->normalizeActivities($contentArray['activities'] ?? []),
        ];
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePositiveInteger(mixed $value, int $default): int
    {
        $intValue = (int) $value;
        return $intValue > 0 ? $intValue : $default;
    }

    private function resolveEndDate(mixed $startDate, mixed $plannedDays, mixed $fallbackEndDate): ?string
    {
        $normalizedStart = $this->normalizeDateString($startDate);
        $normalizedFallback = $this->normalizeDateString($fallbackEndDate);
        $days = $this->normalizePositiveInteger($plannedDays, 0);

        if ($normalizedStart && $days > 0) {
            return Carbon::parse($normalizedStart)->addDays($days - 1)->format('Y-m-d');
        }

        return $normalizedFallback;
    }

    private function normalizeActivityStatus(mixed $value): string
    {
        return match ($value) {
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            default => 'not_started',
        };
    }

    private function normalizeActivityProgress(mixed $status, mixed $progress): int
    {
        $normalizedStatus = $this->normalizeActivityStatus($status);

        if ($normalizedStatus === 'completed') {
            return 100;
        }

        if ($normalizedStatus === 'not_started') {
            return 0;
        }

        $intProgress = (int) $progress;
        return max(0, min(100, $intProgress > 0 ? $intProgress : 50));
    }

    private function activityStatusLabel(string $status): string
    {
        return match ($status) {
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            default => 'Non démarré',
        };
    }
}
