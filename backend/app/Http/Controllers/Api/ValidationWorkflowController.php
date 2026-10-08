<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ValidationWorkflow;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationWorkflowController extends Controller
{
    public function __construct(private readonly ValidationWorkflowService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureRead($request->user());
        $query = ValidationWorkflow::query()->with(['creator:id,name', 'verifier:id,name', 'approver:id,name', 'histories.actor:id,name']);
        if ($request->filled('objet_type')) {
            $query->where('objet_type', mb_strtolower((string) $request->objet_type));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest('id')->paginate(min((int) $request->input('per_page', 20), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCreate($request->user());
        $data = $request->validate([
            'objet_type' => ['required', 'string', 'max:120'],
            'objet_id' => ['required', 'integer', 'min:1'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $workflow = $this->service->create(
            $request->user(),
            $data['objet_type'],
            (int) $data['objet_id'],
            $data['commentaire'] ?? null,
            $data['metadata'] ?? [],
        );

        return response()->json(['data' => $workflow], $workflow->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, ValidationWorkflow $validation): JsonResponse
    {
        $this->ensureRead($request->user());
        $this->assertScope($request->user(), $validation);
        return response()->json(['data' => $validation->load(['creator:id,name', 'verifier:id,name', 'approver:id,name', 'histories.actor:id,name'])]);
    }

    public function verify(Request $request, ValidationWorkflow $validation): JsonResponse
    {
        $this->ensureRq($request->user());
        $this->assertScope($request->user(), $validation);
        $data = $request->validate(['commentaire' => ['nullable', 'string', 'max:5000']]);
        return response()->json(['data' => $this->service->transition($validation, ValidationWorkflowService::VERIFIED, $request->user(), $data['commentaire'] ?? null)]);
    }

    public function approve(Request $request, ValidationWorkflow $validation): JsonResponse
    {
        $this->ensureCeo($request->user());
        $this->assertScope($request->user(), $validation);
        $data = $request->validate(['commentaire' => ['nullable', 'string', 'max:5000']]);
        return response()->json(['data' => $this->service->transition($validation, ValidationWorkflowService::APPROVED, $request->user(), $data['commentaire'] ?? null)]);
    }

    public function refuse(Request $request, ValidationWorkflow $validation): JsonResponse
    {
        $this->ensureRq($request->user());
        $this->assertScope($request->user(), $validation);
        $data = $request->validate(['commentaire' => ['required', 'string', 'max:5000']]);
        return response()->json(['data' => $this->service->transition($validation, ValidationWorkflowService::REFUSED, $request->user(), $data['commentaire'])]);
    }

    private function assertScope(User $user, ValidationWorkflow $workflow): void
    {
        abort_unless($user->isSuperAdmin() || (int) $workflow->enterprise_id === (int) $user->enterprise_id, 403, 'Accès refusé.');
    }

    private function ensureRead(User $user): void
    {
        abort_unless($user && (PermissionHelper::isAdmin($user) || PermissionHelper::can($user, 'validations.read') || PermissionHelper::can($user, 'validations.manage')), 403, 'Permission de lecture manquante.');
    }

    private function ensureCreate(User $user): void
    {
        abort_unless($user && (PermissionHelper::isAdmin($user) || PermissionHelper::can($user, 'validations.create') || PermissionHelper::can($user, 'validations.manage')), 403, 'Permission de création manquante.');
    }

    private function ensureRq(User $user): void
    {
        abort_unless($this->hasRole($user, ['rq', 'responsable_qualite', 'quality_manager', 'hse_manager']) || PermissionHelper::can($user, 'validations.verify') || PermissionHelper::can($user, 'validations.manage') || PermissionHelper::isAdmin($user), 403, 'Droit RQ requis.');
    }

    private function ensureCeo(User $user): void
    {
        abort_unless($this->hasRole($user, ['ceo', 'directeur_general', 'admin_entreprise']) || PermissionHelper::can($user, 'validations.approve') || PermissionHelper::can($user, 'validations.manage') || PermissionHelper::isAdmin($user), 403, 'Droit CEO requis.');
    }

    private function hasRole(User $user, array $roles): bool
    {
        $names = $user->roles->pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();
        $names[] = mb_strtolower((string) $user->role);
        return collect($names)->intersect(array_map('mb_strtolower', $roles))->isNotEmpty();
    }
}
