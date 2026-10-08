<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNonConformityRequest;
use App\Http\Requests\UpdateNonConformityRequest;
use App\Http\Requests\AnalyzeNonConformityRequest;
use App\Http\Requests\ValidateNonConformityRequest;
use App\Http\Requests\VerifyNonConformityRequest;
use App\Models\NonConformity;
use App\Services\NonConformityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NonConformityController extends Controller
{
    public function __construct(protected NonConformityService $service)
    {
    }

    /**
     * Génère un document d'inventaire pour une NC (source_type = non_conformity_report).
     * Retourne le document créé avec son code de nomenclature.
     */
    public function generateReport(Request $request, int $id): JsonResponse
    {
        $nc = NonConformity::with(['site', 'process'])->findOrFail($id);
        $this->authorize('update', $nc);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id'          => $nc->site_id,
            'process_id'       => $nc->process_id,
            'title'            => sprintf('Rapport NC - %s', $nc->reference ?? $nc->id),
            'description'      => $nc->description ?? '',
            'document_kind'    => 'non_conformity_report',
            'source_type'      => 'non_conformity_report',
            'source_id'        => $nc->id,
            'source_updated_at' => $nc->updated_at?->toISOString(),
            'type'             => 'ENR',
            'created_by'       => $request->user()?->id,
            'force_new'        => true,
            'metadata'         => ['nc_id' => $nc->id, 'nc_reference' => $nc->reference],
        ]);

        return response()->json([
            'message'              => 'Document NC créé',
            'generated_document_id' => $document->id,
            'code'                 => $document->code,
            'data'                 => $nc,
        ])->header('X-Generated-Document-Id', (string) $document->id);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NonConformity::class);

        $query = NonConformity::with(['site', 'process', 'audit', 'responsible', 'axes', 'workflowState', 'actions']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('axes')) {
            $axes = is_array($request->axes) ? $request->axes : [$request->axes];
            $query->withAnyAxe($axes);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status')) {
            $query->inState($request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ref', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('requirement_reference', 'like', "%{$request->search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $nonConformities = $query->latest()->paginate($perPage);

        return response()->json($nonConformities);
    }

    public function store(StoreNonConformityRequest $request): JsonResponse
    {
        $this->authorize('create', NonConformity::class);
        
        $nc = $this->service->create($request->validated());

        return response()->json($nc->load(['axes', 'workflowState', 'risks']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $nc = NonConformity::with([
            'site', 'process', 'audit', 'responsible', 'detectedByUser', 
            'verifiedByUser', 'axes', 'workflowState', 'actions.workflowState',
            'documents', 'processes'
        ])->findOrFail($id);

        $this->authorize('view', $nc);

        return response()->json($nc);
    }

    public function update(UpdateNonConformityRequest $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('update', $nc);
        
        $nc = $this->service->update($nc, $request->validated());

        return response()->json($nc);
    }

    public function destroy(int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('delete', $nc);
        
        $nc->delete();

        return response()->json(['message' => 'Non-conformité supprimée'], 200);
    }

    public function analyze(AnalyzeNonConformityRequest $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $nc = $this->service->analyze($nc, $request->validated());

        return response()->json($nc);
    }

    public function validate(ValidateNonConformityRequest $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $nc = $this->service->validate($nc, $request->validated());

        return response()->json($nc);
    }

    public function verify(VerifyNonConformityRequest $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $nc = $this->service->verify($nc, $request->validated());

        return response()->json($nc);
    }

    public function close(int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('close', $nc);
        
        $nc = $this->service->close($nc);

        return response()->json($nc);
    }

    public function addCost(Request $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('addCost', $nc);

        $validated = $request->validate([
            'cost' => 'required|numeric|min:0',
        ]);

        $nc = $this->service->addCost($nc, $validated['cost']);

        return response()->json($nc);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', NonConformity::class);
        
        $filters = $request->only(['site_id', 'axes', 'date_from', 'date_to']);
        $stats = $this->service->getStatistics($filters);

        return response()->json($stats);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('update', $nc);

        $validated = $request->validate([
            'status' => 'required|in:open,analysis,corrective_action,verification,closed,in_progress',
            'comments' => 'nullable|string|max:1000',
        ]);

        $statusMap = [
            'open' => 'open',
            'analysis' => 'in_progress',
            'corrective_action' => 'in_progress',
            'in_progress' => 'in_progress',
            'verification' => 'verified',
            'closed' => 'closed',
        ];

        $update = ['status' => $statusMap[$validated['status']] ?? 'open'];
        if (!empty($validated['comments'])) {
            $update['verification_notes'] = trim(
                (($nc->verification_notes ? $nc->verification_notes . "\n\n" : '') . $validated['comments'])
            );
        }

        $nc->update($update);
        $this->service->syncTaskTrackingAndNotify($nc->fresh(), 'non_conformity_status_updated');

        return response()->json($nc->fresh(['workflowState', 'responsible', 'site', 'process']));
    }

    public function assignResponsible(Request $request, int $id): JsonResponse
    {
        $nc = NonConformity::findOrFail($id);
        $this->authorize('update', $nc);

        $validated = $request->validate([
            'responsible_id' => 'required|exists:users,id',
        ]);

        $nc->update(['responsible_id' => $validated['responsible_id']]);
        $this->service->syncTaskTrackingAndNotify($nc->fresh(), 'non_conformity_assigned');

        return response()->json($nc->fresh(['responsible', 'workflowState', 'site', 'process']));
    }
    
    /**
     * Export NC procedure template as DOCX
     */
    public function exportProcedureTemplate()
    {
        $generator = new \App\Services\Docx\NonConformityDocxGenerator();
        $filePath = $generator->generate(auth()->user()?->enterprise);

        $user = auth()->user();
        $siteId = (int) \App\Models\Site::query()
            ->where('enterprise_id', (int) ($user?->enterprise_id ?? 0))
            ->orderBy('id')
            ->value('id');
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'nonconformity_procedure',
                'title' => 'Procédure de gestion des non-conformités',
                'description' => 'Procédure générée automatiquement depuis le module Non-conformités.',
                'file_source_path' => $filePath,
                'created_by' => $user?->id,
                'type' => 'PRC',
            ]);
        }
        
        $filename = 'Procedure_Non_Conformites.docx';

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
