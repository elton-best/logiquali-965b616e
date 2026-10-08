<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObjectiveRequest;
use App\Http\Requests\UpdateObjectiveRequest;
use App\Http\Requests\UpdateObjectiveProgressRequest;
use App\Models\Objective;
use App\Services\DocumentTypeResolver;
use App\Services\ObjectiveService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ObjectiveController extends Controller
{
    public function __construct(protected ObjectiveService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Objective::class);

        $query = Objective::with(['site', 'responsible', 'indicateur', 'axes', 'workflowState', 'actions']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('axes')) {
            $axes = is_array($request->axes) ? $request->axes : [$request->axes];
            $query->withAnyAxe($axes);
        }

        $objectives = $query->get();

        return response()->json($objectives);
    }

    public function store(StoreObjectiveRequest $request): JsonResponse
    {
        $this->authorize('create', Objective::class);
        
        $objective = $this->service->create($request->validated());

        return response()->json($objective, 201);
    }

    public function show(int $id): JsonResponse
    {
        $objective = Objective::with([
            'site', 'responsible', 'indicateur', 'axes', 'workflowState', 'actions', 'milestones'
        ])->findOrFail($id);

        $this->authorize('view', $objective);

        return response()->json($objective);
    }

    public function update(UpdateObjectiveRequest $request, int $id): JsonResponse
    {
        $objective = Objective::findOrFail($id);
        $this->authorize('update', $objective);
        
        $objective->update($request->validated());

        return response()->json($objective);
    }

    public function destroy(int $id): JsonResponse
    {
        $objective = Objective::findOrFail($id);
        $this->authorize('delete', $objective);
        
        $objective->delete();
        return response()->json(['message' => 'Objectif supprimé'], 200);
    }

    public function updateProgress(UpdateObjectiveProgressRequest $request, int $id): JsonResponse
    {
        $objective = Objective::findOrFail($id);
        $validated = $request->validated();

        $objective = $this->service->updateProgress(
            $objective, 
            $validated['current_value'], 
            $validated['notes'] ?? null
        );

        return response()->json($objective);
    }

    public function addMilestone(Request $request, int $id): JsonResponse
    {
        $objective = Objective::findOrFail($id);
        $this->authorize('addMilestone', $objective);

        $validated = $request->validate([
            'title' => 'required|string',
            'target_date' => 'required|date',
            'target_value' => 'nullable|numeric',
        ]);

        $objective = $this->service->addMilestone($objective, $validated);

        return response()->json($objective);
    }

    public function exportXlsx(Request $request)
    {
        $this->authorize('viewAny', Objective::class);

        $query = Objective::query()->with(['process', 'responsible']);
        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }
        $objectives = $query->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Objectifs');

        $headers = ['N°', 'Titre', 'Processus', 'Responsable', 'Échéance', 'Progression (%)', 'Statut'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($objectives as $index => $objective) {
            $sheet->fromArray([
                $index + 1,
                (string) $objective->title,
                (string) ($objective->process?->title ?? ''),
                (string) ($objective->responsible?->name ?? ''),
                $objective->deadline?->format('Y-m-d'),
                (int) ($objective->progress ?? 0),
                (string) ($objective->status ?? ''),
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'objectifs_' . now()->format('Y-m-d') . '.xlsx';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }
        (new Xlsx($spreadsheet))->save($tempPath);

        $siteId = (int) ($request->integer('site_id') ?: ($request->user()?->site_id ?? 0));
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'objectives_export_xlsx',
                'title' => 'Export objectifs',
                'description' => 'Export XLSX des objectifs.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $request->user()?->id,
                'type' => DocumentTypeResolver::resolveType('objective'),
            ]);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
