<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanActionRequest;
use App\Http\Requests\UpdatePlanActionRequest;
use App\Models\PlanAction;
use App\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanActionController extends Controller
{
    public function __construct(protected ActionService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PlanAction::class);

        $query = PlanAction::with(['actions.responsible', 'responsible', 'axes']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($plans);
    }

    public function store(StorePlanActionRequest $request): JsonResponse
    {
        $this->authorize('create', PlanAction::class);
        
        $plan = $this->service->createPlan($request->validated());

        return response()->json($plan, 201);
    }

    public function show(int $id): JsonResponse
    {
        $plan = PlanAction::with(['actions.responsible', 'responsible', 'axes'])->findOrFail($id);
        $this->authorize('view', $plan);

        return response()->json($plan);
    }

    public function update(UpdatePlanActionRequest $request, int $id): JsonResponse
    {
        $plan = PlanAction::findOrFail($id);
        $this->authorize('update', $plan);
        
        $plan->update($request->validated());

        return response()->json($plan);
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = PlanAction::findOrFail($id);
        $this->authorize('delete', $plan);
        
        $plan->delete();
        return response()->json(['message' => 'Plan d\'action supprimé'], 200);
    }

    public function addAction(Request $request, int $id): JsonResponse
    {
        $plan = PlanAction::findOrFail($id);
        $this->authorize('addAction', $plan);

        $validated = $request->validate([
            'type' => 'required|in:corrective,preventive,improvement,emergency,curative',
            'description' => 'required|string',
            'responsible_id' => 'required|exists:users,id',
            'deadline' => 'required|date',
        ]);

        $validated['plan_action_id'] = $plan->id;
        $action = $this->service->create($validated);

        return response()->json($action, 201);
    }
}
