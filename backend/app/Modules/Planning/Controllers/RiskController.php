<?php

namespace App\Modules\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskRequest;
use App\Http\Requests\UpdateRiskRequest;
use App\Http\Requests\AssessRiskRequest;
use App\Http\Requests\TreatRiskRequest;
use App\Models\Risk;
use App\Services\DocumentTypeResolver;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RiskController extends Controller
{
    public function __construct(protected RiskService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Risk::class);

        $query = Risk::with(['site', 'process', 'responsible', 'axes', 'workflowState', 'actions']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->string('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', (string) $request->string('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('ref', 'ilike', "%{$search}%")
                    ->orWhere('risk_number', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('axes')) {
            $axes = is_array($request->axes) ? $request->axes : [$request->axes];
            $query->withAnyAxe($axes);
        }

        if ($request->has('criticality_min')) {
            $query->highRisk();
        }

        $risks = $query->get();

        return response()->json($risks);
    }

    public function store(StoreRiskRequest $request): JsonResponse
    {
        $this->authorize('create', Risk::class);
        
        $risk = $this->service->create($request->validated());

        return response()->json($risk, 201);
    }

    public function show(int $id): JsonResponse
    {
        $risk = Risk::with([
            'site', 'process', 'responsible', 'axes', 'workflowState', 'actions', 'nonConformities'
        ])->findOrFail($id);

        $this->authorize('view', $risk);

        return response()->json($risk);
    }

    public function update(UpdateRiskRequest $request, int $id): JsonResponse
    {
        $risk = Risk::findOrFail($id);
        $this->authorize('update', $risk);
        
        $risk->update($request->validated());

        return response()->json($risk);
    }

    public function destroy(int $id): JsonResponse
    {
        $risk = Risk::findOrFail($id);
        $this->authorize('delete', $risk);
        
        $risk->delete();
        return response()->json(['message' => 'Risque supprimé'], 200);
    }

    public function assess(AssessRiskRequest $request, int $id): JsonResponse
    {
        $risk = Risk::findOrFail($id);
        $risk = $this->service->assess($risk, $request->validated());

        return response()->json($risk);
    }

    public function treat(TreatRiskRequest $request, int $id): JsonResponse
    {
        $risk = Risk::findOrFail($id);
        $risk = $this->service->treat($risk, $request->validated());

        return response()->json($risk);
    }

    public function mitigate(\App\Http\Requests\MitigateRiskRequest $request, int $id): JsonResponse
    {
        $risk = Risk::findOrFail($id);
        $this->authorize('treat', $risk);
        
        $risk->update([
            'residual_probability' => $request->validated()['residual_probability'],
            'residual_gravity' => $request->validated()['residual_gravity'],
        ]);

        return response()->json($risk, 200);
    }

    public function getMatrix(Request $request): JsonResponse
    {
        $this->authorize('viewMatrix', Risk::class);
        
        $filters = $request->only(['site_id', 'axes', 'type']);
        $matrix = $this->service->getMatrix($filters);

        return response()->json($matrix);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Risk::class);
        
        $stats = $this->service->getStatistics($request->only(['site_id', 'axes']));
        return response()->json($stats);
    }

    /**
     * Generate risk heatmap (4x4 matrix)
     */
    public function heatmap(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Risk::class);
        
        $query = Risk::query();
        
        // Apply filters
        if ($request->has('process_id')) {
            $query->where('process_id', $request->process_id);
        }
        
        if ($request->has('axes')) {
            $axes = is_array($request->axes) ? $request->axes : explode(',', $request->axes);
            $query->withAnyAxe($axes);
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        $risks = $query->with('process')->get();
        
        // Build matrix data (4x4 grid)
        $matrix = [];
        for ($p = 1; $p <= 4; $p++) {
            for ($g = 1; $g <= 4; $g++) {
                $cellRisks = $risks->filter(function($r) use ($p, $g) {
                    return $r->probability == $p && $r->gravity == $g;
                });
                
                if ($cellRisks->isNotEmpty()) {
                    $criticality = $p * $g;
                    $matrix[] = [
                        'probability' => $p,
                        'gravity' => $g,
                        'count' => $cellRisks->count(),
                        'criticality' => $criticality,
                        'level' => $this->getCriticalityLevel($criticality),
                        'risks' => $cellRisks->map(fn($r) => [
                            'id' => $r->id,
                            'title' => $r->title,
                            'reference' => $r->reference,
                            'process' => $r->process->name ?? null,
                        ])->values(),
                    ];
                }
            }
        }
        
        // Calculate summary
        $summary = [
            'total' => $risks->count(),
            'low' => $risks->where('criticality_level', 'low')->count(),
            'medium' => $risks->where('criticality_level', 'medium')->count(),
            'high' => $risks->where('criticality_level', 'high')->count(),
            'critical' => $risks->where('criticality_level', 'critical')->count(),
        ];
        
        return response()->json([
            'matrix' => $matrix,
            'summary' => $summary,
            'total' => $risks->count(),
        ]);
    }

    private function getCriticalityLevel(int $criticality): string
    {
        return match(true) {
            $criticality >= 12 => 'critical',
            $criticality >= 8 => 'high',
            $criticality >= 4 => 'medium',
            default => 'low',
        };
    }

    /**
     * Export Risks as DOCX (Plan Maîtrise Risques)
     */
    public function exportDocx(Request $request)
    {
        $query = Risk::with(['process', 'responsible']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Export global ou par processus
        $processId = $request->get('process_id');
        
        $generator = new \App\Services\Docx\RiskDocxGenerator();
        $filePath = $generator->generate($processId, $request->get('site_id'));
        $downloadPath = $filePath;
        $deleteAfterSend = true;
        $document = null;

        // Si export ciblé par processus, synchroniser aussi dans l'inventaire documentaire
        if ($processId) {
            $process = \App\Models\Process::query()->find((int) $processId);
            $siteId = (int) ($request->get('site_id') ?: ($process?->site_id ?? 0));
            if ($siteId > 0) {
                $storagePath = sprintf(
                    'generated/risk-plans/plan_maitrise_risques_processus_%d_%s.docx',
                    (int) $processId,
                    now()->format('YmdHis')
                );
                Storage::put($storagePath, file_get_contents($filePath));
                $downloadPath = storage_path('app/' . $storagePath);
                $deleteAfterSend = false;

                $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                    'site_id' => $siteId,
                    'process_id' => (int) $processId,
                    'process_code' => (string) ($process?->code ?? $process?->abbreviation ?? ''),
                    'process_name' => (string) ($process?->title ?? $process?->name ?? $process?->nom ?? ''),
                    'document_kind' => 'risk_plan',
                    'source_type' => 'risk_plan',
                    'source_id' => (int) $processId,
                    'source_updated_at' => $process?->updated_at?->toISOString(),
                    'title' => 'Plan de maîtrise des risques - ' . ($process?->title ?? $process?->name ?? $process?->nom ?? ('Processus ' . $processId)),
                    'description' => 'Plan de maîtrise des risques généré automatiquement pour le processus.',
                    'file_source_path' => $storagePath,
                    'created_by' => auth()->id(),
                    'type' => DocumentTypeResolver::resolveType('risk_opportunity'),
                    'force_new' => true,
                ]);
            }
        }
        
        $filename = 'Plan_Maitrise_Risques_' . ($processId ? "Processus_{$processId}" : 'Global') . '.docx';

        $response = response()->download($downloadPath, $filename);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }

        return $deleteAfterSend ? $response->deleteFileAfterSend(true) : $response;
    }
}
