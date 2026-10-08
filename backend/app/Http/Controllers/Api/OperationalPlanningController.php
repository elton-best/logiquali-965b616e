<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\ComplianceObligationAction;
use App\Models\OperationalProject;
use App\Models\OperationalProjectActivity;
use App\Models\OperationalProjectTask;
use App\Models\ProcessObjective;
use App\Models\ProcessRiskOpportunity;
use App\Models\TaskTracking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class OperationalPlanningController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $siteId = $this->resolveSiteId($request);

        $actions = [
            ...$this->collectRiskOpportunityActions($siteId, $user->enterprise_id),
            ...$this->collectObjectiveActions($siteId, $user->enterprise_id),
            ...$this->collectComplianceActions($siteId, $user->enterprise_id),
            ...$this->collectStandaloneActions($siteId, $user->enterprise_id),
            ...$this->collectMyTasksConsolidatedActions($request, $siteId),
        ];

        $actions = collect($actions)
            ->unique('id')
            ->values()
            ->all();

        usort($actions, function (array $a, array $b): int {
            $aDate = strtotime((string) ($a['due_date'] ?? ''));
            $bDate = strtotime((string) ($b['due_date'] ?? ''));
            return $aDate <=> $bDate;
        });

        $projects = collect();
        if (Schema::hasTable('operational_projects')) {
            $projectsQuery = OperationalProject::query()
                ->with([
                    'site:id,name',
                    'projectManager:id,name,email',
                    'activities.responsibleUser:id,name,email',
                    'activities.tasks.assignedTo:id,name,email',
                    'activities.tasks.responsibleUser:id,name,email',
                    'processes:id,title,code',
                ])
                ->orderByDesc('updated_at');

            if ($user->user_type !== 'super_admin' && $user->enterprise_id) {
                $projectsQuery->where('enterprise_id', $user->enterprise_id);
            }
            if ($siteId) {
                $projectsQuery->where('site_id', $siteId);
            }

            $projects = $projectsQuery->get();
        }

        return response()->json([
            'data' => [
                'consolidated_actions' => $actions,
                'projects' => $projects,
            ],
            'meta' => [
                'site_id' => $siteId,
                'actions_count' => count($actions),
                'projects_count' => $projects->count(),
            ],
        ]);
    }

    public function storeProject(Request $request): JsonResponse
    {
        if (!Schema::hasTable('operational_projects')) {
            return response()->json([
                'message' => 'Module de projets opérationnels non initialisé (migration manquante).',
            ], 503);
        }

        $user = $request->user();

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planned,in_progress,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'project_manager_id' => 'nullable|exists:users,id',
            'release_notes' => 'nullable|string|max:2000',
            'process_ids' => 'nullable|array',
            'process_ids.*' => 'integer|exists:processes,id',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['site_id'] = $validated['site_id'] ?? $this->resolveSiteId($request);
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $processIds = $validated['process_ids'] ?? [];
        unset($validated['process_ids']);
        $project = OperationalProject::create($validated);
        $project->processes()->sync($processIds);
        $project = $project->load('projectManager:id,name,email', 'site:id,name', 'processes:id,title,code');

        return response()->json(['data' => $project], 201);
    }

    public function updateProject(Request $request, OperationalProject $project): JsonResponse
    {
        if (!Schema::hasTable('operational_projects')) {
            return response()->json([
                'message' => 'Module de projets opérationnels non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $project);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planned,in_progress,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'project_manager_id' => 'nullable|exists:users,id',
            'release_notes' => 'nullable|string|max:2000',
            'process_ids' => 'nullable|array',
            'process_ids.*' => 'integer|exists:processes,id',
        ]);

        $validated['updated_by'] = $request->user()->id;
        $processIds = $validated['process_ids'] ?? null;
        unset($validated['process_ids']);
        $project->update($validated);
        if ($processIds !== null) {
            $project->processes()->sync($processIds);
        }

        return response()->json(['data' => $project->fresh()->load('projectManager:id,name,email', 'site:id,name', 'processes:id,title,code')]);
    }

    public function uploadProjectEvidence(Request $request, OperationalProject $project): JsonResponse
    {
        if (!Schema::hasColumn('operational_projects', 'release_evidences')) {
            return response()->json([
                'message' => 'Champs de preuves de libération non initialisés (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $project);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:20480',
        ]);

        $file = $validated['file'];
        $storedPath = $file->store('operational-projects/release-evidences', 'public');
        $publicUrl = URL::to(Storage::url($storedPath));

        $existing = $project->release_evidences;
        if (!is_array($existing)) {
            $existing = [];
        }

        $existing[] = [
            'name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'url' => $publicUrl,
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => $request->user()?->name,
            'uploaded_by_id' => $request->user()?->id,
        ];

        $project->update([
            'release_evidences' => $existing,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Preuve ajoutée avec succès.',
            'data' => $project->fresh()->load('projectManager:id,name,email', 'site:id,name'),
        ], 201);
    }

    public function deleteProjectEvidence(Request $request, OperationalProject $project, int $index): JsonResponse
    {
        if (!Schema::hasColumn('operational_projects', 'release_evidences')) {
            return response()->json([
                'message' => 'Champs de preuves de libération non initialisés (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $project);

        $existing = $project->release_evidences;
        if (!is_array($existing) || !array_key_exists($index, $existing)) {
            return response()->json([
                'message' => 'Preuve introuvable.',
            ], 404);
        }

        $evidence = $existing[$index] ?? null;
        if (is_array($evidence) && !empty($evidence['path'])) {
            Storage::disk('public')->delete((string) $evidence['path']);
        }

        array_splice($existing, $index, 1);

        $project->update([
            'release_evidences' => array_values($existing),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Preuve supprimée avec succès.',
            'data' => $project->fresh()->load('projectManager:id,name,email', 'site:id,name'),
        ]);
    }

    public function destroyProject(Request $request, OperationalProject $project): JsonResponse
    {
        if (!Schema::hasTable('operational_projects')) {
            return response()->json([
                'message' => 'Module de projets opérationnels non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $project);
        $project->delete();

        return response()->json(['message' => 'Projet supprimé']);
    }

    public function storeActivity(Request $request, OperationalProject $project): JsonResponse
    {
        if (!Schema::hasTable('operational_project_activities')) {
            return response()->json([
                'message' => 'Module des activités opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $project);
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planned,in_progress,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'position' => 'nullable|integer|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
        ]);

        $validated['position'] = $validated['position'] ?? ((int) $project->activities()->max('position') + 1);
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $activity = $project->activities()->create($validated)->load('responsibleUser:id,name,email');
        $this->syncOperationalTracking($activity, $activity->responsible_user_id, $activity->status, $activity->progress, $user->id);

        return response()->json(['data' => $activity], 201);
    }

    public function updateActivity(Request $request, OperationalProjectActivity $activity): JsonResponse
    {
        if (!Schema::hasTable('operational_project_activities')) {
            return response()->json([
                'message' => 'Module des activités opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $activity->project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planned,in_progress,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'position' => 'nullable|integer|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
        ]);

        $validated['updated_by'] = $request->user()->id;
        $activity->update($validated);
        $this->syncOperationalTracking($activity, $activity->responsible_user_id, $activity->status, $activity->progress, $request->user()->id);

        return response()->json(['data' => $activity->fresh()->load('responsibleUser:id,name,email')]);
    }

    public function destroyActivity(Request $request, OperationalProjectActivity $activity): JsonResponse
    {
        if (!Schema::hasTable('operational_project_activities')) {
            return response()->json([
                'message' => 'Module des activités opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $activity->project);
        $activity->delete();

        return response()->json(['message' => 'Activité supprimée']);
    }

    public function storeTask(Request $request, OperationalProjectActivity $activity): JsonResponse
    {
        if (!Schema::hasTable('operational_project_tasks')) {
            return response()->json([
                'message' => 'Module des tâches opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $activity->project);
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,done,blocked',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'position' => 'nullable|integer|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
        ]);

        // Keep compatibility with older tracking logic while making responsible explicit.
        if (!array_key_exists('assigned_to', $validated) && array_key_exists('responsible_user_id', $validated)) {
            $validated['assigned_to'] = $validated['responsible_user_id'];
        }

        $validated['position'] = $validated['position'] ?? ((int) $activity->tasks()->max('position') + 1);
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $task = $activity->tasks()->create($validated)->load('responsibleUser:id,name,email');
        $responsibleId = $task->responsible_user_id ?: $task->assigned_to;
        $this->syncOperationalTracking($task, $responsibleId, $task->status, $task->progress, $user->id);

        return response()->json(['data' => $task], 201);
    }

    public function updateTask(Request $request, OperationalProjectTask $task): JsonResponse
    {
        if (!Schema::hasTable('operational_project_tasks')) {
            return response()->json([
                'message' => 'Module des tâches opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $task->activity->project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,done,blocked',
            'priority' => 'nullable|in:low,medium,high,critical',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'position' => 'nullable|integer|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
        ]);

        if (!array_key_exists('assigned_to', $validated) && array_key_exists('responsible_user_id', $validated)) {
            $validated['assigned_to'] = $validated['responsible_user_id'];
        }

        $validated['updated_by'] = $request->user()->id;
        $task->update($validated);
        $responsibleId = $task->responsible_user_id ?: $task->assigned_to;
        $this->syncOperationalTracking($task, $responsibleId, $task->status, $task->progress, $request->user()->id);

        return response()->json(['data' => $task->fresh()->load('responsibleUser:id,name,email')]);
    }

    public function destroyTask(Request $request, OperationalProjectTask $task): JsonResponse
    {
        if (!Schema::hasTable('operational_project_tasks')) {
            return response()->json([
                'message' => 'Module des tâches opérationnelles non initialisé (migration manquante).',
            ], 503);
        }

        $this->assertProjectAccess($request, $task->activity->project);
        $task->delete();

        return response()->json(['message' => 'Tâche supprimée']);
    }

    private function resolveSiteId(Request $request): ?int
    {
        $siteId = $request->integer('site_id');
        if ($siteId > 0) {
            return $siteId;
        }

        if ($request->user()?->site_id) {
            return (int) $request->user()->site_id;
        }

        return null;
    }

    private function assertProjectAccess(Request $request, OperationalProject $project): void
    {
        $user = $request->user();
        if ($user->user_type === 'super_admin') {
            return;
        }

        abort_unless(
            $user->enterprise_id && (int) $user->enterprise_id === (int) $project->enterprise_id,
            403,
            'Accès refusé à ce projet'
        );
    }

    private function collectRiskOpportunityActions(?int $siteId, ?int $enterpriseId): array
    {
        $query = ProcessRiskOpportunity::query()->with([
            'process:id,title,site_id',
            'responsibleUser:id,name',
        ]);

        if ($siteId) {
            $query->whereHas('process', fn ($q) => $q->where('site_id', $siteId));
        } elseif ($enterpriseId) {
            $query->whereHas('process.site', fn ($q) => $q->where('enterprise_id', $enterpriseId));
        }

        $rows = [];
        $userNames = User::query()->pluck('name', 'id')->all();
        foreach ($query->orderByDesc('updated_at')->get() as $item) {
            $actions = $this->normalizeActionsPayload($item->planned_actions);
            if (count($actions) === 0 && is_string($item->actions_prevues) && trim($item->actions_prevues) !== '') {
                $actions = $this->convertTextActionsToArray($item->actions_prevues);
            }

            foreach ($actions as $index => $action) {
                $responsibleId = isset($action['responsible_user_id']) ? (int) $action['responsible_user_id'] : null;
                $responsibleName = $action['responsible'] ?? null;
                if (!$responsibleName && $responsibleId) {
                    $responsibleName = $userNames[$responsibleId] ?? null;
                }
                if (!$responsibleName) {
                    $responsibleName = $item->responsibleUser?->name;
                }

                $actionType = $action['action_type'] ?? null;
                $actionTypeLabel = match ($actionType) {
                    'preventive' => 'Préventive',
                    'corrective' => 'Corrective',
                    'control' => 'Maîtrise',
                    default => null,
                };
                $actionTitle = $action['title'] ?? 'Action planifiée';
                if ($actionTypeLabel) {
                    $actionTitle = "[{$actionTypeLabel}] {$actionTitle}";
                }

                $rows[] = [
                    'id' => "riskopp-{$item->id}-{$index}",
                    'source_module' => 'risks_opportunities',
                    'source_type' => $item->type,
                    'source_id' => $item->id,
                    'source_title' => $item->title,
                    'process_id' => $item->process_id,
                    'process_name' => $item->process?->title,
                    'title' => $actionTitle,
                    'description' => $action['description'] ?? null,
                    'action_type' => $actionType,
                    'status' => $action['status'] ?? $item->status,
                    'priority' => $item->niveau ?? null,
                    'responsible_user_id' => $responsibleId ?: $item->responsible_user_id,
                    'responsible_name' => $responsibleName,
                    'start_date' => $action['start_date'] ?? null,
                    'due_date' => $action['due_date'] ?? optional($item->target_date)->format('Y-m-d'),
                    'progress' => isset($action['progress']) ? (int) $action['progress'] : null,
                    'created_at' => optional($item->created_at)->toISOString(),
                    'updated_at' => optional($item->updated_at)->toISOString(),
                ];
            }
        }

        return $rows;
    }

    private function collectObjectiveActions(?int $siteId, ?int $enterpriseId): array
    {
        $query = ProcessObjective::query()->with(['process:id,title,site_id']);

        if ($siteId) {
            $query->whereHas('process', fn ($q) => $q->where('site_id', $siteId));
        } elseif ($enterpriseId) {
            $query->whereHas('process.site', fn ($q) => $q->where('enterprise_id', $enterpriseId));
        }

        $rows = [];
        $userNames = User::query()->pluck('name', 'id')->all();
        foreach ($query->orderByDesc('updated_at')->get() as $item) {
            $actions = $this->normalizeActionsPayload($item->planned_actions);
            if (count($actions) === 0 && is_string($item->action_plan) && trim($item->action_plan) !== '') {
                $actions = [['title' => $item->action_plan]];
            }

            foreach ($actions as $index => $action) {
                $responsibleId = isset($action['responsible_user_id']) ? (int) $action['responsible_user_id'] : null;
                $responsibleName = $action['responsible'] ?? null;
                if (!$responsibleName && $responsibleId) {
                    $responsibleName = $userNames[$responsibleId] ?? null;
                }

                $rows[] = [
                    'id' => "objective-{$item->id}-{$index}",
                    'source_module' => 'objectives',
                    'source_type' => 'objective',
                    'source_id' => $item->id,
                    'source_title' => $item->title,
                    'process_id' => $item->process_id,
                    'process_name' => $item->process?->title,
                    'title' => $action['title'] ?? 'Action objectif',
                    'description' => $action['description'] ?? null,
                    'status' => $action['status'] ?? $item->status,
                    'priority' => null,
                    'responsible_user_id' => $responsibleId,
                    'responsible_name' => $responsibleName,
                    'start_date' => $action['start_date'] ?? null,
                    'due_date' => $action['due_date'] ?? optional($item->target_date)->format('Y-m-d'),
                    'progress' => isset($action['progress']) ? (int) $action['progress'] : null,
                    'created_at' => optional($item->created_at)->toISOString(),
                    'updated_at' => optional($item->updated_at)->toISOString(),
                ];
            }
        }

        return $rows;
    }

    private function collectComplianceActions(?int $siteId, ?int $enterpriseId): array
    {
        if (!Schema::hasTable('compliance_obligation_actions')) {
            return [];
        }

        $query = ComplianceObligationAction::query()->with([
            'text:id,aspect_id,description,regulatory_reference',
            'text.aspect:id,site_id',
            'responsible:id,name',
        ]);

        if ($siteId) {
            $query->whereHas('text.aspect', fn ($q) => $q->where('site_id', $siteId));
        } elseif ($enterpriseId) {
            $query->whereHas('text.aspect.site', fn ($q) => $q->where('enterprise_id', $enterpriseId));
        }

        return $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (ComplianceObligationAction $action): array {
                return [
                    'id' => "compliance-action-{$action->id}",
                    'source_module' => 'compliance_obligations',
                    'source_type' => 'regulatory_action',
                    'source_id' => $action->id,
                    'source_title' => $action->text?->regulatory_reference ?: 'Obligation de conformité',
                    'process_id' => null,
                    'process_name' => null,
                    'title' => $action->title,
                    'description' => $action->text?->description,
                    'status' => $action->status,
                    'priority' => null,
                    'responsible_user_id' => $action->responsible_id,
                    'responsible_name' => $action->responsible?->name,
                    'start_date' => null,
                    'due_date' => optional($action->due_date)->format('Y-m-d'),
                    'progress' => null,
                    'created_at' => optional($action->created_at)->toISOString(),
                    'updated_at' => optional($action->updated_at)->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    private function collectStandaloneActions(?int $siteId, ?int $enterpriseId): array
    {
        $query = Action::query()->with(['process:id,title,site_id', 'responsible:id,name']);

        if ($siteId) {
            $query->where('site_id', $siteId);
        } elseif ($enterpriseId) {
            $query->where('enterprise_id', $enterpriseId);
        }

        return $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Action $action): array {
                return [
                    'id' => "action-{$action->id}",
                    'source_module' => 'actions',
                    'source_type' => $action->type,
                    'source_id' => $action->id,
                    'source_title' => $action->title,
                    'process_id' => $action->process_id,
                    'process_name' => $action->process?->title,
                    'title' => $action->title,
                    'description' => $action->description,
                    'status' => $action->status,
                    'priority' => $action->priority,
                    'responsible_user_id' => $action->responsible_id,
                    'responsible_name' => $action->responsible?->name,
                    'start_date' => optional($action->start_date)->format('Y-m-d'),
                    'due_date' => optional($action->deadline)->format('Y-m-d'),
                    'progress' => $action->progress,
                    'created_at' => optional($action->created_at)->toISOString(),
                    'updated_at' => optional($action->updated_at)->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeActionsPayload(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : [];
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    private function convertTextActionsToArray(string $raw): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '' && !str_starts_with($line, '[META]'))
            ->values()
            ->map(fn ($line) => ['title' => $line])
            ->all();
    }

    private function collectMyTasksConsolidatedActions(Request $request, ?int $siteId): array
    {
        $month = $request->query('month', now()->format('Y-m'));
        $myTasksRequest = Request::create('/api/v1/my-tasks', 'GET', [
            'month' => $month,
            'status' => 'all',
        ]);
        $myTasksRequest->setUserResolver(fn () => $request->user());

        $response = app(MyTasksController::class)->index($myTasksRequest);
        $payload = $response->getData(true);
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return collect($rows)
            ->filter(function (array $row) use ($siteId) {
                if (!$siteId) {
                    return true;
                }
                return (int) ($row['site_id'] ?? 0) === (int) $siteId || empty($row['site_id']);
            })
            ->map(function (array $row): array {
                return [
                    'id' => 'mytask-' . ($row['type'] ?? 'task') . '-' . ($row['id'] ?? uniqid()),
                    'source_module' => 'my_tasks',
                    'source_type' => (string) ($row['type'] ?? 'task'),
                    'source_id' => $row['id'] ?? null,
                    'source_title' => (string) ($row['title'] ?? 'Tâche'),
                    'process_id' => $row['process_id'] ?? null,
                    'process_name' => $row['process_name'] ?? null,
                    'title' => (string) ($row['title'] ?? 'Tâche'),
                    'description' => $row['description'] ?? null,
                    'status' => $row['status'] ?? null,
                    'priority' => $row['priority'] ?? null,
                    'responsible_user_id' => $row['responsible_id'] ?? null,
                    'responsible_name' => $row['responsible'] ?? null,
                    'start_date' => $row['start_date'] ?? null,
                    'due_date' => $row['deadline'] ?? $row['end_date'] ?? null,
                    'progress' => isset($row['progress_rate']) ? (int) $row['progress_rate'] : null,
                    'created_at' => null,
                    'updated_at' => null,
                ];
            })
            ->values()
            ->all();
    }

    private function syncOperationalTracking(
        OperationalProjectActivity|OperationalProjectTask $item,
        ?int $responsibleId,
        ?string $status,
        mixed $progress,
        ?int $actorId
    ): void {
        $userId = (int) ($responsibleId ?? 0);
        if ($userId <= 0) {
            return;
        }

        $mappedStatus = $this->mapOperationalStatusToTrackingStatus($status);
        $mappedProgress = $this->mapOperationalStatusToProgress($status, $progress);

        TaskTracking::updateOrCreate(
            [
                'user_id' => $userId,
                'trackable_type' => $item::class,
                'trackable_id' => $item->id,
            ],
            [
                'status' => $mappedStatus,
                'progress_rate' => $mappedProgress,
                'notes' => 'Synchronisation automatique planification opérationnelle',
                'tracked_at' => now(),
            ]
        );
    }

    private function mapOperationalStatusToTrackingStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            'done', 'completed', 'closed', 'cancelled' => 'termine',
            'in_progress', 'on_hold', 'blocked' => 'en_cours',
            default => 'non_demarre',
        };
    }

    private function mapOperationalStatusToProgress(?string $status, mixed $progress): int
    {
        $value = strtolower(trim((string) $status));
        $manual = is_numeric($progress) ? (int) $progress : null;

        if (in_array($value, ['done', 'completed', 'closed', 'cancelled'], true)) {
            return 100;
        }
        if (in_array($value, ['in_progress', 'on_hold', 'blocked'], true)) {
            return $manual !== null ? max(1, min(99, $manual)) : 50;
        }

        return 0;
    }
}
