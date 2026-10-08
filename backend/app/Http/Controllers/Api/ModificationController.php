<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModificationResource;
use App\Models\Modification;
use App\Models\Site;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\Request;

class ModificationController extends Controller
{
    public function index(Request $request)
    {
        $modifications = Modification::with(['site', 'responsible', 'validator', 'initiator', 'resultOwner', 'monitoringOwner'])
            ->whereHas('site', fn ($query) => $query->where('enterprise_id', $request->user()->enterprise_id))
            ->latest('id')
            ->paginate(20);
        return ModificationResource::collection($modifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'initiator_id' => 'nullable|exists:users,id',
            'number' => 'required|string|max:255',
            'date' => 'required|date',
            'object' => 'required|string|max:255',
            'description' => 'required|string',
            'scope' => 'nullable|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'integer|exists:documents,id',
            'responsible_id' => 'nullable|exists:users,id',
            'result_owner_id' => 'nullable|exists:users,id',
            'monitoring_owner_id' => 'nullable|exists:users,id',
            'validated_by' => 'nullable|exists:users,id',
            'validated_at' => 'nullable|date',
            'status' => 'required|in:pending,approved,implemented,rejected',
            'workflow_status' => 'nullable|in:brouillon,verifie_rq,approuve_ceo,refuse',
        ]);

        $validated['initiator_id'] = $validated['initiator_id'] ?? $request->user()->id;
        $validated['workflow_status'] = $validated['workflow_status'] ?? ValidationWorkflowService::DRAFT;
        $site = Site::query()->findOrFail($validated['site_id']);
        abort_unless($request->user()->isSuperAdmin() || (int) $site->enterprise_id === (int) $request->user()->enterprise_id, 403, 'Site hors périmètre.');
        $modification = Modification::create($validated);
        return new ModificationResource($modification->load(['site', 'responsible', 'validator', 'initiator', 'resultOwner', 'monitoringOwner']));
    }

    public function show(Request $request, Modification $modification)
    {
        $this->assertScope($request, $modification);
        return new ModificationResource($modification->load(['site', 'responsible', 'validator', 'initiator', 'resultOwner', 'monitoringOwner']));
    }

    public function update(Request $request, Modification $modification)
    {
        $this->assertScope($request, $modification);
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'initiator_id' => 'sometimes|exists:users,id',
            'number' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'object' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'scope' => 'nullable|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'integer|exists:documents,id',
            'responsible_id' => 'nullable|exists:users,id',
            'result_owner_id' => 'nullable|exists:users,id',
            'monitoring_owner_id' => 'nullable|exists:users,id',
            'validated_by' => 'nullable|exists:users,id',
            'validated_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,approved,implemented,rejected',
            'workflow_status' => 'sometimes|in:brouillon,verifie_rq,approuve_ceo,refuse',
        ]);

        $modification->update($validated);
        return new ModificationResource($modification->load(['site', 'responsible', 'validator', 'initiator', 'resultOwner', 'monitoringOwner']));
    }

    public function destroy(Request $request, Modification $modification)
    {
        $this->assertScope($request, $modification);
        $modification->delete();
        return response()->json(null, 204);
    }

    public function submit(Request $request, Modification $modification): \Illuminate\Http\JsonResponse
    {
        $this->assertScope($request, $modification);
        abort_unless((int) $modification->responsible_id === (int) $request->user()->id || $request->user()->isEnterpriseAdmin(), 403, 'Responsable de modification requis.');
        $workflow = app(ValidationWorkflowService::class)->create($request->user(), 'modification', $modification->id, $request->input('commentaire'), ['document_ids' => $modification->document_ids ?? []]);
        return response()->json(['data' => $workflow], 201);
    }

    private function assertScope(Request $request, Modification $modification): void
    {
        abort_unless($request->user()?->isSuperAdmin() || (int) $modification->site?->enterprise_id === (int) $request->user()?->enterprise_id, 403, 'Accès refusé.');
    }
}
