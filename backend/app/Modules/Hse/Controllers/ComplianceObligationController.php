<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ComplianceObligationAspect;
use App\Models\ComplianceObligationText;
use App\Models\Norm;
use App\Services\DocumentTypeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ComplianceObligationController extends Controller
{
    public function index(Request $request)
    {
        $query = ComplianceObligationText::query()->with([
            'aspect.norms:id,code,name',
            'responsible:id,name,email',
            'actions.responsible:id,name,email',
        ]);

        if ($request->filled('site_id')) {
            $siteId = (int) $request->site_id;
            $query->whereHas('aspect', fn ($aspect) => $aspect->where('site_id', $siteId));
        }
        if ($request->filled('compliance_status')) {
            $query->where('compliance_status', $request->compliance_status);
        }
        if ($request->filled('validity_status')) {
            $query->where('validity_status', $request->validity_status);
        }
        if ($request->filled('q')) {
            $term = '%' . trim((string) $request->q) . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('ref', 'ilike', $term)
                    ->orWhere('regulatory_reference', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term)
                    ->orWhere('applicable_requirement', 'ilike', $term)
                    ->orWhereHas('aspect', fn ($aspect) => $aspect->where('name', 'ilike', $term));
            });
        }

        $perPage = max(1, min((int) $request->integer('per_page', 50), 300));
        $rows = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $rows->getCollection()->map(fn (ComplianceObligationText $row) => $this->serializeText($row))->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aspect_id' => 'nullable|exists:compliance_obligation_aspects,id',
            'site_id' => 'required_without:aspect_id|nullable|exists:sites,id',
            'aspect_name' => 'required_without:aspect_id|nullable|string|max:255',
            'aspect_description' => 'nullable|string',
            'norm_ids' => 'nullable|array',
            'norm_ids.*' => 'exists:norms,id',
            'regulatory_reference' => 'required|string',
            'description' => 'nullable|string',
            'applicable_requirement' => 'nullable|string',
            'watch_source' => 'nullable|string|max:255',
            'entry_into_force_date' => 'nullable|date',
            'regulatory_change_status' => 'nullable|in:existing,new,modified',
            'validity_status' => 'nullable|in:in_force,obsolete',
            'compliance_status' => 'nullable|in:compliant,partial,non_compliant,not_applicable',
            'actions_corrective_preventive' => 'nullable|string',
            'deadline' => 'nullable|date',
            'responsible_id' => 'nullable|exists:users,id',
            'evaluation_frequency' => 'nullable|in:monthly,quarterly,semiannual,annual,on_demand',
            'comments' => 'nullable|string',
            'actions' => 'nullable|array',
            'actions.*.title' => 'required|string|max:1000',
            'actions.*.responsible_id' => 'nullable|exists:users,id',
            'actions.*.due_date' => 'nullable|date',
            'actions.*.status' => 'nullable|in:pending,in_progress,done,cancelled',
            'actions.*.comments' => 'nullable|string',
        ]);

        $aspect = $this->resolveAspect($validated);

        $text = ComplianceObligationText::create([
            'aspect_id' => $aspect->id,
            'ref' => $this->generateRef(),
            'regulatory_reference' => trim((string) $validated['regulatory_reference']),
            'description' => $validated['description'] ?? null,
            'applicable_requirement' => $validated['applicable_requirement'] ?? null,
            'watch_source' => $validated['watch_source'] ?? null,
            'entry_into_force_date' => $validated['entry_into_force_date'] ?? null,
            'regulatory_change_status' => $validated['regulatory_change_status'] ?? null,
            'validity_status' => $validated['validity_status'] ?? null,
            'compliance_status' => $validated['compliance_status'] ?? 'not_applicable',
            'actions_corrective_preventive' => $validated['actions_corrective_preventive'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
            'responsible_id' => $validated['responsible_id'] ?? null,
            'evaluation_frequency' => $validated['evaluation_frequency'] ?? null,
            'comments' => $validated['comments'] ?? null,
        ]);

        foreach ($validated['actions'] ?? [] as $action) {
            $text->actions()->create([
                'title' => trim((string) $action['title']),
                'responsible_id' => $action['responsible_id'] ?? null,
                'due_date' => $action['due_date'] ?? null,
                'status' => $action['status'] ?? 'pending',
                'comments' => $action['comments'] ?? null,
            ]);
        }

        $text->load(['aspect.norms:id,code,name', 'responsible:id,name,email', 'actions.responsible:id,name,email']);

        return response()->json(['data' => $this->serializeText($text)], 201);
    }

    public function update(Request $request, int $id)
    {
        $text = ComplianceObligationText::with('aspect')->findOrFail($id);

        $validated = $request->validate([
            'aspect_id' => 'sometimes|nullable|exists:compliance_obligation_aspects,id',
            'regulatory_reference' => 'sometimes|string',
            'description' => 'sometimes|nullable|string',
            'applicable_requirement' => 'sometimes|nullable|string',
            'watch_source' => 'sometimes|nullable|string|max:255',
            'entry_into_force_date' => 'sometimes|nullable|date',
            'regulatory_change_status' => 'sometimes|nullable|in:existing,new,modified',
            'validity_status' => 'sometimes|nullable|in:in_force,obsolete',
            'compliance_status' => 'sometimes|nullable|in:compliant,partial,non_compliant,not_applicable',
            'actions_corrective_preventive' => 'sometimes|nullable|string',
            'deadline' => 'sometimes|nullable|date',
            'responsible_id' => 'sometimes|nullable|exists:users,id',
            'evaluation_frequency' => 'sometimes|nullable|in:monthly,quarterly,semiannual,annual,on_demand',
            'comments' => 'sometimes|nullable|string',
            'actions' => 'sometimes|array',
            'actions.*.title' => 'required|string|max:1000',
            'actions.*.responsible_id' => 'nullable|exists:users,id',
            'actions.*.due_date' => 'nullable|date',
            'actions.*.status' => 'nullable|in:pending,in_progress,done,cancelled',
            'actions.*.comments' => 'nullable|string',
        ]);

        $text->update([
            'aspect_id' => array_key_exists('aspect_id', $validated) ? ($validated['aspect_id'] ?? $text->aspect_id) : $text->aspect_id,
            'regulatory_reference' => $validated['regulatory_reference'] ?? $text->regulatory_reference,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $text->description,
            'applicable_requirement' => array_key_exists('applicable_requirement', $validated) ? $validated['applicable_requirement'] : $text->applicable_requirement,
            'watch_source' => array_key_exists('watch_source', $validated) ? $validated['watch_source'] : $text->watch_source,
            'entry_into_force_date' => array_key_exists('entry_into_force_date', $validated) ? $validated['entry_into_force_date'] : $text->entry_into_force_date,
            'regulatory_change_status' => array_key_exists('regulatory_change_status', $validated) ? $validated['regulatory_change_status'] : $text->regulatory_change_status,
            'validity_status' => array_key_exists('validity_status', $validated) ? $validated['validity_status'] : $text->validity_status,
            'compliance_status' => array_key_exists('compliance_status', $validated) ? ($validated['compliance_status'] ?? 'not_applicable') : $text->compliance_status,
            'actions_corrective_preventive' => array_key_exists('actions_corrective_preventive', $validated) ? $validated['actions_corrective_preventive'] : $text->actions_corrective_preventive,
            'deadline' => array_key_exists('deadline', $validated) ? $validated['deadline'] : $text->deadline,
            'responsible_id' => array_key_exists('responsible_id', $validated) ? $validated['responsible_id'] : $text->responsible_id,
            'evaluation_frequency' => array_key_exists('evaluation_frequency', $validated) ? $validated['evaluation_frequency'] : $text->evaluation_frequency,
            'comments' => array_key_exists('comments', $validated) ? $validated['comments'] : $text->comments,
        ]);

        if (array_key_exists('actions', $validated)) {
            $text->actions()->delete();
            foreach ($validated['actions'] ?? [] as $action) {
                $text->actions()->create([
                    'title' => trim((string) $action['title']),
                    'responsible_id' => $action['responsible_id'] ?? null,
                    'due_date' => $action['due_date'] ?? null,
                    'status' => $action['status'] ?? 'pending',
                    'comments' => $action['comments'] ?? null,
                ]);
            }
        }

        $text->load(['aspect.norms:id,code,name', 'responsible:id,name,email', 'actions.responsible:id,name,email']);

        return response()->json(['data' => $this->serializeText($text)]);
    }

    public function destroy(int $id)
    {
        $text = ComplianceObligationText::findOrFail($id);
        $text->delete();
        return response()->json(['message' => 'Obligation supprimée.']);
    }

    public function exportXlsx(Request $request)
    {
        $query = ComplianceObligationText::query()->with([
            'aspect.norms:id,code,name',
            'responsible:id,name',
            'actions.responsible:id,name',
        ]);

        if ($request->filled('site_id')) {
            $siteId = (int) $request->site_id;
            $query->whereHas('aspect', fn ($aspect) => $aspect->where('site_id', $siteId));
        }

        $rows = $query->get()
            ->sortBy([
                fn (ComplianceObligationText $row) => (int) ($row->aspect_id ?? 0),
                fn (ComplianceObligationText $row) => (int) $row->id,
            ])
            ->values();

        // Export is built only from database data.
        // The provided file is used as structural inspiration (title + register columns).
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registre');
        $sheet->setCellValue('A1', 'REGISTRE DE VEILLE RÉGLEMENTAIRE');
        $sheet->setCellValue('B1', 'Réf : CAM / REG/ 001');
        $sheet->setCellValue('A2', 'Version : 02');
        $sheet->setCellValue('A3', 'Page : 1');
        $sheet->fromArray([
            'N°',
            'Thème/ volet / aspect',
            'Normes liées',
            'Texte / Référence Réglementaire',
            'Description / Libelé',
            'Exigences Applicables',
            'Source de Veille',
            "DATE D'ENTREE EN VIGEUR",
            'STATUT (existant, nouveau, modifié)',
            'En vigueur / Obsolète',
            'État de Conformité',
            'Actions Correctives / Préventives',
            'Échéance',
            "Responsable de l'action",
            "FREQUENCE D'EVALUATION DE CONFORMITE",
            'Commentaires',
        ], null, 'A5');

        $startRow = 10;
        $rowIndex = $startRow;

        $lineNumber = 1;
        $groupedByAspect = $rows->groupBy(fn (ComplianceObligationText $row) => (string) ($row->aspect_id ?? 0));

        foreach ($groupedByAspect as $groupRows) {
            $groupStartRow = $rowIndex;
            $aspectName = trim((string) ($groupRows->first()?->aspect?->name ?? ''));

            foreach ($groupRows as $row) {
                $actionLines = $row->actions
                    ->map(fn ($action) => trim((string) ($action->title ?? '')))
                    ->filter()
                    ->values()
                    ->all();

                $actionsText = implode("\n", $actionLines);
                if ($actionsText === '') {
                    $actionsText = (string) ($row->actions_corrective_preventive ?? '');
                }

                $normCodes = $row->aspect?->norms?->pluck('code')->filter()->values()->all() ?? [];

                $sheet->fromArray([
                    $lineNumber,
                    $aspectName,
                    implode(', ', $normCodes),
                    $row->regulatory_reference,
                    $row->description,
                    $row->applicable_requirement,
                    $row->watch_source,
                    $row->entry_into_force_date?->format('Y-m-d'),
                    $this->regulatoryChangeStatusLabel($row->regulatory_change_status),
                    $this->validityStatusLabel($row->validity_status),
                    $this->complianceStatusLabel($row->compliance_status),
                    $actionsText,
                    $row->deadline?->format('Y-m-d'),
                    $row->responsible?->name,
                    $this->evaluationFrequencyLabel($row->evaluation_frequency),
                    $row->comments,
                ], null, "A{$rowIndex}");

                $lineNumber++;
                $rowIndex++;
            }

            $groupEndRow = $rowIndex - 1;
            if ($groupEndRow > $groupStartRow) {
                $sheet->mergeCells("B{$groupStartRow}:B{$groupEndRow}");
                $sheet->mergeCells("C{$groupStartRow}:C{$groupEndRow}");
                $sheet->getStyle("B{$groupStartRow}:B{$groupEndRow}")
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle("C{$groupStartRow}:C{$groupEndRow}")
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
        }

        foreach (range('A', 'P') as $column) {
            $sheet->getStyle($column . ($startRow) . ':' . $column . max($rowIndex, $startRow))->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'registre_veille_reglementaire_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $user = $request->user();
        $siteId = (int) ($request->input('site_id') ?? 0);
        if ($siteId <= 0 && $user?->site_id) {
            $siteId = (int) $user->site_id;
        }
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'compliance_register_xlsx',
                'title' => 'Registre de veille réglementaire',
                'description' => 'Export XLSX du registre de veille réglementaire.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => DocumentTypeResolver::resolveType('compliance_obligation'),
            ]);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function downloadTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modele import');
        $sheet->setCellValue('A1', 'MODELE D\'IMPORT - REGISTRE DE VEILLE RÉGLEMENTAIRE');
        $sheet->setCellValue('B1', 'À compléter avant téléversement');
        $sheet->setCellValue('A2', 'Instructions');
        $sheet->setCellValue('B2', 'Conserver la structure. Une ligne = un texte réglementaire. Répéter le volet/aspect si nécessaire.');
        $sheet->fromArray([
            'N°',
            'Thème/ volet / aspect',
            'Normes liées',
            'Texte / Référence Réglementaire',
            'Description / Libelé',
            'Exigences Applicables',
            'Source de Veille',
            "DATE D'ENTREE EN VIGEUR",
            'STATUT (existant, nouveau, modifié)',
            'En vigueur / Obsolète',
            'État de Conformité',
            'Actions Correctives / Préventives',
            'Échéance',
            "Responsable de l'action",
            "FREQUENCE D'EVALUATION DE CONFORMITE",
            'Commentaires',
        ], null, 'A5');

        $sampleRows = [
            [1, 'Veille réglementaire', 'ISO 9001', 'Décret / Arrêté', 'Intitulé du texte', 'Exigence à respecter', 'Site officiel', '2026-01-15', 'existant', 'En vigueur', 'Conforme', 'Action 1', '2026-03-31', 'Nom responsable', 'Trimestriel', ''],
            [2, 'Veille réglementaire', 'ISO 14001', 'Code / Loi', 'Second texte', 'Autre exigence', 'Journal officiel', '2026-02-01', 'nouveau', 'En vigueur', 'Partiellement conforme', 'Action 2', '2026-06-30', 'Nom responsable', 'Mensuel', ''],
        ];

        $sheet->fromArray($sampleRows, null, 'A6');

        foreach (range('A', 'P') as $column) {
            $sheet->getStyle($column . '5:' . $column . '8')->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'modele_import_registre_veille_reglementaire_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function importXlsx(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $siteId = (int) $validated['site_id'];
        $file = $request->file('file');
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $importedRows = 0;
        $createdAspects = 0;
        $updatedTexts = 0;
        $currentAspectName = null;

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                if ($index < 6) {
                    continue;
                }

                $aspectName = trim((string) ($row['B'] ?? ''));
                if ($aspectName !== '') {
                    $currentAspectName = $aspectName;
                }

                $regulatoryReference = trim((string) ($row['D'] ?? ''));
                if (!$currentAspectName || $regulatoryReference === '') {
                    continue;
                }

                $aspect = ComplianceObligationAspect::firstOrCreate(
                    [
                        'site_id' => $siteId,
                        'name' => $currentAspectName,
                    ],
                    [
                        'description' => null,
                    ]
                );

                if ($aspect->wasRecentlyCreated) {
                    $createdAspects++;
                }

                $normCodes = collect(explode(',', (string) ($row['C'] ?? '')))
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->values();

                if ($normCodes->isNotEmpty()) {
                    $normIds = Norm::query()
                        ->whereIn('code', $normCodes->all())
                        ->pluck('id')
                        ->all();

                    if ($normIds !== []) {
                        $aspect->norms()->syncWithoutDetaching($normIds);
                    }
                }

                $payload = [
                    'description' => $this->normalizeStringOrNull($row['E'] ?? null),
                    'applicable_requirement' => $this->normalizeStringOrNull($row['F'] ?? null),
                    'watch_source' => $this->normalizeStringOrNull($row['G'] ?? null),
                    'entry_into_force_date' => $this->normalizeSpreadsheetDate($row['H'] ?? null),
                    'regulatory_change_status' => $this->mapChangeStatus($row['I'] ?? null),
                    'validity_status' => $this->mapValidityStatus($row['J'] ?? null),
                    'compliance_status' => $this->mapComplianceStatus($row['K'] ?? null),
                    'actions_corrective_preventive' => $this->normalizeStringOrNull($row['L'] ?? null),
                    'deadline' => $this->normalizeSpreadsheetDate($row['M'] ?? null),
                    'responsible_id' => $this->resolveUserIdFromName($row['N'] ?? null),
                    'evaluation_frequency' => $this->mapEvaluationFrequency($row['O'] ?? null),
                    'comments' => $this->normalizeStringOrNull($row['P'] ?? null),
                ];

                $text = ComplianceObligationText::with('actions')
                    ->where('aspect_id', $aspect->id)
                    ->where('regulatory_reference', $regulatoryReference)
                    ->first();

                if ($text) {
                    $text->update($payload);
                    $text->actions()->delete();
                    $updatedTexts++;
                } else {
                    $text = ComplianceObligationText::create([
                        'aspect_id' => $aspect->id,
                        'ref' => $this->generateRef(),
                        'regulatory_reference' => $regulatoryReference,
                        ...$payload,
                    ]);
                }

                $actions = preg_split('/\r\n|\r|\n/', (string) ($row['L'] ?? '')) ?: [];
                foreach ($actions as $actionTitle) {
                    $normalizedTitle = trim((string) $actionTitle);
                    if ($normalizedTitle === '') {
                        continue;
                    }

                    $text->actions()->create([
                        'title' => $normalizedTitle,
                        'responsible_id' => $payload['responsible_id'],
                        'due_date' => $payload['deadline'],
                        'status' => 'pending',
                        'comments' => $payload['comments'],
                    ]);
                }

                $importedRows++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import Excel des obligations de conformité terminé.',
                'data' => [
                    'imported_rows' => $importedRows,
                    'created_aspects' => $createdAspects,
                    'updated_texts' => $updatedTexts,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function resolveAspect(array $validated): ComplianceObligationAspect
    {
        if (!empty($validated['aspect_id'])) {
            return ComplianceObligationAspect::findOrFail((int) $validated['aspect_id']);
        }

        $aspect = ComplianceObligationAspect::create([
            'site_id' => (int) $validated['site_id'],
            'name' => trim((string) ($validated['aspect_name'] ?? 'Aspect')),
            'description' => $validated['aspect_description'] ?? null,
        ]);

        $aspect->norms()->sync(array_values(array_unique($validated['norm_ids'] ?? [])));

        return $aspect;
    }

    private function generateRef(): string
    {
        $year = now()->format('Y');
        $prefix = "OBL-{$year}-";
        $last = ComplianceObligationText::where('ref', 'like', $prefix . '%')
            ->orderByDesc('ref')
            ->value('ref');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches) === 1) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix . Str::padLeft((string) $seq, 4, '0');
    }

    private function serializeText(ComplianceObligationText $row): array
    {
        return [
            'id' => $row->id,
            'ref' => $row->ref,
            'aspect' => [
                'id' => $row->aspect?->id,
                'site_id' => $row->aspect?->site_id,
                'name' => $row->aspect?->name,
                'description' => $row->aspect?->description,
                'norms' => $row->aspect?->norms?->map(fn ($norm) => [
                    'id' => $norm->id,
                    'code' => $norm->code,
                    'name' => $norm->name,
                ])->values() ?? [],
            ],
            'regulatory_reference' => $row->regulatory_reference,
            'description' => $row->description,
            'applicable_requirement' => $row->applicable_requirement,
            'watch_source' => $row->watch_source,
            'entry_into_force_date' => $row->entry_into_force_date?->format('Y-m-d'),
            'regulatory_change_status' => $row->regulatory_change_status,
            'validity_status' => $row->validity_status,
            'compliance_status' => $row->compliance_status,
            'actions_corrective_preventive' => $row->actions_corrective_preventive,
            'deadline' => $row->deadline?->format('Y-m-d'),
            'responsible_id' => $row->responsible_id,
            'responsible_name' => $row->responsible?->name,
            'evaluation_frequency' => $row->evaluation_frequency,
            'comments' => $row->comments,
            'actions' => $row->actions->map(fn ($action) => [
                'id' => $action->id,
                'title' => $action->title,
                'responsible_id' => $action->responsible_id,
                'responsible_name' => $action->responsible?->name,
                'due_date' => $action->due_date?->format('Y-m-d'),
                'status' => $action->status,
                'comments' => $action->comments,
            ])->values(),
        ];
    }

    private function regulatoryChangeStatusLabel(?string $value): ?string
    {
        return match ($value) {
            'existing' => 'Existant',
            'new' => 'Nouveau',
            'modified' => 'Modifié',
            default => $value,
        };
    }

    private function validityStatusLabel(?string $value): ?string
    {
        return match ($value) {
            'in_force' => 'En vigueur',
            'obsolete' => 'Obsolète',
            default => $value,
        };
    }

    private function complianceStatusLabel(?string $value): ?string
    {
        return match ($value) {
            'compliant' => 'Conforme',
            'partial' => 'Partiellement conforme',
            'non_compliant' => 'Non conforme',
            'not_applicable' => 'Non applicable',
            default => $value,
        };
    }

    private function evaluationFrequencyLabel(?string $value): ?string
    {
        return match ($value) {
            'monthly' => 'Mensuelle',
            'quarterly' => 'Trimestrielle',
            'semiannual' => 'Semestrielle',
            'annual' => 'Annuelle',
            'on_demand' => 'Ponctuelle',
            default => $value,
        };
    }

    private function normalizeStringOrNull(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeSpreadsheetDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapChangeStatus(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        return match ($normalized) {
            'nouveau', 'new' => 'new',
            'modifié', 'modifie', 'modified' => 'modified',
            'existant', 'existing' => 'existing',
            default => null,
        };
    }

    private function mapValidityStatus(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        return match ($normalized) {
            'en vigueur', 'in_force', 'in force' => 'in_force',
            'obsolète', 'obsolete', 'obsoletee' => 'obsolete',
            default => null,
        };
    }

    private function mapComplianceStatus(mixed $value): string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        return match ($normalized) {
            'conforme', 'compliant' => 'compliant',
            'partiellement conforme', 'partial' => 'partial',
            'non conforme', 'non_compliant' => 'non_compliant',
            default => 'not_applicable',
        };
    }

    private function mapEvaluationFrequency(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        return match ($normalized) {
            'mensuelle', 'monthly' => 'monthly',
            'trimestrielle', 'quarterly' => 'quarterly',
            'semestrielle', 'semiannual', 'semi-annuelle' => 'semiannual',
            'annuelle', 'annual' => 'annual',
            'ponctuelle', 'on_demand', 'on demand' => 'on_demand',
            default => null,
        };
    }

    private function resolveUserIdFromName(mixed $value): ?int
    {
        $name = trim((string) ($value ?? ''));
        if ($name === '') {
            return null;
        }

        return \App\Models\User::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->value('id');
    }
}
