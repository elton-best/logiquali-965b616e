<?php

namespace App\Modules\Improvement\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActionRequest;
use App\Http\Requests\UpdateActionRequest;
use App\Http\Requests\UpdateActionProgressRequest;
use App\Helpers\PermissionHelper;
use App\Models\Action;
use App\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActionController extends Controller
{
    public function __construct(protected ActionService $service)
    {
    }

    /**
     * Menu "Mes actions" sur le tableau de bord (RT-13 / REQ-8.7-03).
     * Retourne les actions de l'utilisateur connecté triées par échéance
     * avec filtres par statut et métriques récapitulatives.
     */
    public function myActions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $query = Action::with(['responsible', 'planAction', 'workflowState', 'axes', 'process:id,title,code'])
            ->where('responsible_id', (int) $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->integer('process_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        $actions = $query->orderByRaw("CASE WHEN status IN ('completed', 'verified', 'cancelled') THEN 1 ELSE 0 END")
            ->orderBy('deadline', 'asc')
            ->paginate($request->integer('per_page', 25));

        // Statistiques pour les filtres cliquables (RT-08)
        $counts = [
            'total' => Action::where('responsible_id', $user->id)->count(),
            'planned' => Action::where('responsible_id', $user->id)->where('status', 'planned')->count(),
            'in_progress' => Action::where('responsible_id', $user->id)->where('status', 'in_progress')->count(),
            'overdue' => Action::where('responsible_id', $user->id)->whereNotIn('status', ['completed', 'verified', 'cancelled'])->whereNotNull('deadline')->whereDate('deadline', '<', now())->count(),
            'completed' => Action::where('responsible_id', $user->id)->whereIn('status', ['completed', 'verified'])->count(),
        ];

        return response()->json([
            'success' => true,
            'counts' => $counts,
            'data' => $actions,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Action::class);
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $query = Action::with(['responsible', 'planAction', 'workflowState', 'axes', 'process:id,title,code']);
        $canManageAllActions = PermissionHelper::isAdmin($user)
            || PermissionHelper::can($user, 'actions.manage');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->inState($request->status);
        }

        if (!$canManageAllActions) {
            $query->where('responsible_id', (int) $user->id);
        } elseif ($request->has('responsible_id')) {
            $query->where('responsible_id', $request->responsible_id);
        }

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('process_id')) {
            $query->where('process_id', $request->process_id);
        }

        if ($request->has('priority')) {
            $priority = $request->priority === 'urgent' ? 'critical' : $request->priority;
            $query->where('priority', $priority);
        }

        if ($request->has('search')) {
            $q = (string) $request->search;
            $query->where(function ($inner) use ($q) {
                $inner->where('ref', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $actions = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($actions);
    }

    public function store(StoreActionRequest $request): JsonResponse
    {
        $this->authorize('create', Action::class);
        
        $action = $this->service->create($request->validated());

        return response()->json($action, 201);
    }

    public function show(int $id): JsonResponse
    {
        $action = Action::with([
            'responsible', 'planAction', 'workflowState', 'axes', 
            'nonConformities', 'audits', 'risks', 'objectives', 'reclamations'
        ])->findOrFail($id);

        $this->authorize('view', $action);

        return response()->json($action);
    }

    public function update(UpdateActionRequest $request, int $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $this->authorize('update', $action);
        
        $action = $this->service->update($action, $request->validated());

        return response()->json($action);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $this->authorize('update', $action);

        $validated = $request->validate([
            'status' => 'required|in:draft,assigned,in_progress,completed,verified,closed,cancelled,planned',
            'comments' => 'nullable|string|max:1000',
        ]);

        $mappedStatus = match ($validated['status']) {
            'draft', 'assigned', 'planned' => 'planned',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'verified', 'closed' => 'verified',
            'cancelled' => 'cancelled',
            default => 'planned',
        };

        $updates = ['status' => $mappedStatus];
        if (!empty($validated['comments'])) {
            $actor = Auth::user();
            $updates['progress_notes'] = array_values(array_filter([
                ...($action->progress_notes ?? []),
                [
                    'date' => now()->format('Y-m-d H:i:s'),
                    'note' => $validated['comments'],
                    'user_id' => Auth::id(),
                    'user_name' => $actor?->name ?? $actor?->username,
                ],
            ]));
        }

        if ($mappedStatus === 'completed') {
            $updates['progress'] = 100;
        }

        $action->update($updates);

        return response()->json($action->fresh(['responsible', 'workflowState', 'planAction']));
    }

    public function destroy(int $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $this->authorize('delete', $action);
        
        $action->delete();
        return response()->json(['message' => 'Action supprimée'], 200);
    }

    public function updateProgress(UpdateActionProgressRequest $request, int $id): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('ActionController::updateProgress called', ['id' => $id, 'user' => $request->user()?->id ?? null]);

        $action = Action::find($id);
        if (!$action) {
            \Illuminate\Support\Facades\Log::warning('Action not found in updateProgress', ['id' => $id]);
            abort(404);
        }

        $validated = $request->validated();

        $action = $this->service->updateProgress(
            $action,
            $validated['progress'],
            $validated['notes'] ?? null
        );

        return response()->json($action);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $this->authorize('verify', $action);

        $validated = $request->validate([
            'is_effective' => 'required|boolean',
            'verification_notes' => 'nullable|string',
        ]);

        $action = $this->service->verify($action, $validated);

        return response()->json($action);
    }

    public function close(int $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $this->authorize('close', $action);
        
        $action = $this->service->complete($action);

        return response()->json($action);
    }
    
    /**
     * Download action plan template
     */
    public function downloadPlanTemplate(): BinaryFileResponse
    {
        $generator = new \App\Exports\ActionPlanTemplate(Auth::user()?->enterprise);
        $filePath = $generator->generate();

        $user = Auth::user();
        $siteId = (int) ($user?->site_id ?? 0);
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
                'document_kind' => 'action_plan_template_xlsx',
                'title' => 'Template plan d’actions',
                'description' => 'Modèle XLSX de plan d’actions.',
                'file_source_path' => $filePath,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'FOR',
            ]);
        }
        
        $filename = 'Template_Plan_Actions.xlsx';
        
        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
    
    /**
     * Import action plan from XLSX
     */
    public function importPlan(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'site_id' => 'nullable|integer|exists:sites,id',
        ]);
        
        $file = $request->file('file');
        $siteId = $request->input('site_id');

        // Store file temporarily
        $path = $file->store('temp');
        $fullPath = storage_path('app/' . $path);

        try {
            $importer = new \App\Imports\ActionPlanImport();
            $results = $importer->import($fullPath, $siteId);

            $user = Auth::user();
            if ($user) {
                $errorMessages = [];
                foreach (($results['error_details'] ?? []) as $detail) {
                    foreach (($detail['errors'] ?? []) as $msg) {
                        $errorMessages[] = $msg;
                    }
                }

                event(new \App\Events\System\ImportCompleted(
                    $user,
                    $file->getClientOriginalName(),
                    '',
                    (int) ($results['success'] ?? 0),
                    (int) ($results['errors'] ?? 0) === 0,
                    $errorMessages
                ));
            }

            // Clean up temp file
            Storage::delete($path);

            $httpStatus = count($results['errors']) > 0 ? 422 : 200;

            return response()->json($results, $httpStatus);

        } catch (\Exception $e) {
            // Clean up on error
            Storage::delete($path);

            $user = Auth::user();
            if ($user) {
                event(new \App\Events\System\ImportCompleted(
                    $user,
                    $file->getClientOriginalName(),
                    '',
                    0,
                    false,
                    [$e->getMessage()]
                ));
            }

            return response()->json([
                'success' => 0,
                'errors' => 1,
                'error_details' => [
                    [
                        'row' => 0,
                        'errors' => ['Import failed: ' . $e->getMessage()],
                    ],
                ],
            ], 500);
        }
    }
}
