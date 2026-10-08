<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlainteRequest;
use App\Http\Requests\UpdatePlainteRequest;
use App\Http\Requests\InvestigatePlainteRequest;
use App\Http\Requests\ResolvePlainteRequest;
use App\Models\Plainte;
use App\Services\PlainteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlainteController extends Controller
{
    public function __construct(protected PlainteService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Plainte::class);

        $query = Plainte::with(['site', 'assignedUser', 'respondedByUser', 'workflowState', 'actions']);

        // Filtrer les plaintes confidentielles selon permissions
        if (!$request->user()->can('viewConfidential', Plainte::class)) {
            $query->where('confidential', false);
        }

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status')) {
            $query->inState($request->status);
        }

        if ($request->has('stakeholder_type')) {
            $query->where('stakeholder_type', $request->stakeholder_type);
        }

        if ($request->boolean('overdue')) {
            $query->where('due_date', '<', now())
                  ->whereNull('closed_date');
        }

        if ($request->boolean('confidential')) {
            $query->where('confidential', true);
        }

        $plaintes = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($plaintes);
    }

    public function store(StorePlainteRequest $request): JsonResponse
    {
        $plainte = $this->service->create($request->validated());

        return response()->json($plainte, 201);
    }

    public function show(int $id): JsonResponse
    {
        $plainte = Plainte::with([
            'site', 'assignedUser', 'respondedByUser', 'workflowState', 'actions', 'documents', 'processes'
        ])->findOrFail($id);

        $this->authorize('view', $plainte);

        // Masquer informations sensibles si plainte anonyme et utilisateur sans permission
        if ($plainte->anonymous && !request()->user()->can('viewAnonymousDetails', $plainte)) {
            $plainte->makeHidden(['plaignant_name', 'plaignant_email', 'plaignant_phone']);
        }

        return response()->json($plainte);
    }

    public function update(UpdatePlainteRequest $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $plainte = $this->service->update($plainte, $request->validated());

        return response()->json($plainte);
    }

    public function destroy(int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('delete', $plainte);
        
        $plainte->delete();
        return response()->json(['message' => 'Plainte supprimée'], 200);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('assign', $plainte);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $plainte = $this->service->assign($plainte, $validated['assigned_to'], $validated['priority'] ?? null);

        return response()->json($plainte);
    }

    public function investigate(InvestigatePlainteRequest $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('investigate', $plainte);

        $plainte = $this->service->investigate($plainte, $request->validated());

        return response()->json($plainte);
    }

    public function respond(Request $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('respond', $plainte);

        $validated = $request->validate([
            'immediate_response' => 'required|string',
            'send_notification' => 'boolean',
        ]);

        $plainte = $this->service->respond(
            $plainte,
            $validated['immediate_response'],
            $validated['send_notification'] ?? false
        );

        return response()->json($plainte);
    }

    public function resolve(ResolvePlainteRequest $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('resolve', $plainte);

        $plainte = $this->service->resolve($plainte, $request->validated());

        return response()->json($plainte);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $plainte = Plainte::findOrFail($id);
        $this->authorize('close', $plainte);

        $validated = $request->validate([
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
            'satisfaction_comment' => 'nullable|string',
        ]);

        $plainte = $this->service->close($plainte, $validated);

        return response()->json($plainte);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Plainte::class);
        
        $stats = $this->service->getStatistics($request->only(['site_id', 'year', 'category']));
        return response()->json($stats);
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', Plainte::class);

        $validated = $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'filters' => 'nullable|array',
        ]);

        $file = $this->service->export($validated['format'], $validated['filters'] ?? []);

        return response()->json(['url' => $file]);
    }
}
