<?php

namespace App\Modules\Improvement\Controllers;

use App\Models\Action;
use App\Services\ActionService;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\UpdateReclamationRequest;
use App\Http\Requests\RespondReclamationRequest;
use App\Models\Reclamation;
use App\Services\ReclamationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReclamationController extends Controller
{
    public function __construct(protected ReclamationService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reclamation::class);

        $query = Reclamation::with(['site', 'customer', 'responsible', 'axes', 'workflowState', 'nonConformities']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->inState($request->status);
        }

        $reclamations = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($reclamations);
    }

    public function store(StoreReclamationRequest $request): JsonResponse
    {
        $this->authorize('create', Reclamation::class);
        
        $reclamation = $this->service->create($request->validated());

        return response()->json($reclamation, 201);
    }

    public function show(int $id): JsonResponse
    {
        $reclamation = Reclamation::with([
            'site', 'customer', 'responsible', 'axes', 'workflowState', 'nonConformities', 'actions'
        ])->findOrFail($id);

        $this->authorize('view', $reclamation);

        return response()->json($reclamation);
    }

    public function update(UpdateReclamationRequest $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $this->authorize('update', $reclamation);
        
        $validated = $request->validated();
        if (array_key_exists('category', $validated)) {
            $validated['category'] = $this->normalizeCategory($validated['category']);
        } elseif (array_key_exists('type', $validated)) {
            $validated['category'] = $this->normalizeCategory($validated['type']);
            unset($validated['type']);
        }
        if (array_key_exists('status', $validated)) {
            $validated['status'] = $this->normalizeStatus($validated['status']);
        }
        $reclamation->update($validated);

        if (array_key_exists('actions', $validated)) {
            $reclamation->actions()->detach();
            foreach ($validated['actions'] ?? [] as $actionData) {
                $payload = array_merge($actionData, [
                    'site_id' => $reclamation->site_id,
                    'process_id' => $actionData['process_id'] ?? null,
                    'title' => $actionData['title'] ?? ($actionData['description'] ?? 'Action réclamation'),
                    'description' => $actionData['description'] ?? '',
                    'source' => 'complaint',
                ]);
                $action = app(\App\Services\ActionService::class)->create($payload);
                $reclamation->addAction($action);
            }
        }

        $this->service->syncTaskTrackingAndNotify($reclamation->fresh(), 'reclamation_updated');

        return response()->json($reclamation->fresh(['actions']));
    }

    public function destroy(int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $this->authorize('delete', $reclamation);
        
        $reclamation->delete();
        return response()->json(['message' => 'Réclamation supprimée'], 200);
    }

    public function analyze(Request $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $this->authorize('analyze', $reclamation);

        $validated = $request->validate([
            'root_cause' => 'nullable|string',
            'immediate_action' => 'nullable|string',
            'preventive_action' => 'nullable|string',
        ]);

        $reclamation = $this->service->analyze($reclamation, $validated);

        return response()->json($reclamation);
    }

    public function respond(RespondReclamationRequest $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $validated = $request->validated();

        $reclamation = $this->service->respond(
            $reclamation, 
            $validated['response'], 
            $validated['send_email'] ?? false
        );

        return response()->json($reclamation);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $this->authorize('close', $reclamation);

        $validated = $request->validate([
            'satisfaction_score' => 'nullable|integer|min:1|max:5',
            'satisfaction_comments' => 'nullable|string',
        ]);

        $reclamation = $this->service->close($reclamation, $validated);

        return response()->json($reclamation);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Reclamation::class);
        
        $stats = $this->service->getStatistics($request->only(['site_id', 'axes', 'year']));
        return response()->json($stats);
    }

    private function normalizeCategory(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $map = [
            'product' => 'product_quality',
            'quality' => 'product_quality',
            'service' => 'service_quality',
            'delivery' => 'delivery_delay',
            'safety' => 'other',
            'environment' => 'other',
        ];

        $allowed = [
            'product_quality',
            'product_defect',
            'service_quality',
            'delivery_delay',
            'delivery_error',
            'documentation',
            'packaging',
            'billing',
            'communication',
            'other',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return in_array($normalized, $allowed, true) ? $normalized : 'other';
    }

    private function normalizeStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'open', 'pending', '' => 'pending',
            'in_analysis', 'analysis', 'in_treatment', 'in_progress', 'processing' => 'in_progress',
            'resolved', 'done' => 'resolved',
            'closed', 'termine', 'terminé' => 'closed',
            default => 'pending',
        };
    }
}
