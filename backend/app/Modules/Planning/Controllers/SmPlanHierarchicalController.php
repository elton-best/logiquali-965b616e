<?php

namespace App\Modules\Planning\Controllers;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Plan;

use App\Http\Controllers\Controller;
use App\Modules\Planning\Models\SmPlanActivity;
use App\Modules\Planning\Models\SmPlanSubActivity;
use App\Modules\Planning\Models\SmPlanAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmPlanHierarchicalController extends Controller
{
    /**
     * Get the full 3-level SM Plan tree (Activité -> Sous-activité -> Action) without weights.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $year = (int) $request->query('year', date('Y'));
        $query = SmPlanActivity::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->where('year', $year)
            ->with([
                'site:id,name',
                'process:id,code,title',
                'subActivities' => function ($q) {
                    $q->orderBy('order')->with([
                        'actions' => function ($aq) {
                            $aq->with(['responsible:id,name', 'rescheduler:id,name'])
                               ->orderBy('id');
                        }
                    ]);
                }
            ])
            ->orderBy('order');

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->query('process_id'));
        }

        $activities = $query->get();

        // Calculate summary counts
        $totalActions = 0;
        $completedActions = 0;
        $delayedActions = 0;
        $inProgressActions = 0;

        foreach ($activities as $act) {
            foreach ($act->subActivities as $sub) {
                foreach ($sub->actions as $action) {
                    $totalActions++;
                    if ($action->status === 'realise') $completedActions++;
                    elseif ($action->status === 'en_retard') $delayedActions++;
                    elseif ($action->status === 'en_cours') $inProgressActions++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $activities,
            'summary' => [
                'total_activities' => $activities->count(),
                'total_actions' => $totalActions,
                'completed_actions' => $completedActions,
                'in_progress_actions' => $inProgressActions,
                'delayed_actions' => $delayedActions,
                'completion_rate' => $totalActions > 0 ? round(($completedActions / $totalActions) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Export SM Plan (Canevas Ben à 3 niveaux) to Excel (by enterprise and by site / year).
     */
    public function exportXlsx(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $filters = [
            'site_id' => $request->filled('site_id') ? (int) $request->site_id : ($user->site_id ?: null),
            'process_id' => $request->filled('process_id') ? (int) $request->process_id : null,
            'year' => (int) $request->query('year', date('Y')),
        ];

        $exportService = app(\App\Modules\Planning\Services\SmPlanExportService::class);
        $tempFile = $exportService->exportSmPlanXlsx($user->enterprise_id, $filters);

        $siteSuffix = !empty($filters['site_id']) ? "_site_{$filters['site_id']}" : '_tous_sites';
        $filename = "plan_sm_{$filters['year']}" . $siteSuffix . '_' . date('Ymd_His') . '.xlsx';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a new level 1 Activity.
     */
    public function storeActivity(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'year' => 'nullable|integer',
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['year'] = $validated['year'] ?? (int) date('Y');
        $validated['created_by'] = $user->id;

        $activity = SmPlanActivity::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activité du Plan SM créée avec succès.',
            'data' => $activity,
        ], 201);
    }

    /**
     * Update level 1 Activity.
     */
    public function updateActivity(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $activity = SmPlanActivity::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'year' => 'nullable|integer',
            'code' => 'nullable|string|max:50',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $validated['updated_by'] = $user->id;
        $activity->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour.',
            'data' => $activity,
        ]);
    }

    /**
     * Delete level 1 Activity (cascades to sub-activities and actions).
     */
    public function destroyActivity(int $id): JsonResponse
    {
        $user = Auth::user();
        $activity = SmPlanActivity::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée avec succès.',
        ]);
    }

    /**
     * Store a new level 2 Sub-Activity (Mandatory per REQ-6.3-06).
     */
    public function storeSubActivity(Request $request, int $activityId): JsonResponse
    {
        $user = Auth::user();
        $activity = SmPlanActivity::where('enterprise_id', $user->enterprise_id)->findOrFail($activityId);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $validated['activity_id'] = $activity->id;
        $validated['created_by'] = $user->id;

        $subActivity = SmPlanSubActivity::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sous-activité créée avec succès.',
            'data' => $subActivity,
        ], 201);
    }

    /**
     * Update level 2 Sub-Activity.
     */
    public function updateSubActivity(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $subActivity = SmPlanSubActivity::whereHas('activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $validated['updated_by'] = $user->id;
        $subActivity->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sous-activité mise à jour.',
            'data' => $subActivity,
        ]);
    }

    /**
     * Delete level 2 Sub-Activity.
     */
    public function destroySubActivity(int $id): JsonResponse
    {
        $user = Auth::user();
        $subActivity = SmPlanSubActivity::whereHas('activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($id);

        $subActivity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sous-activité supprimée.',
        ]);
    }

    /**
     * Store level 3 Action / Task (No weights per REQ-6.3-06).
     */
    public function storeAction(Request $request, int $subActivityId): JsonResponse
    {
        $user = Auth::user();
        $subActivity = SmPlanSubActivity::whereHas('activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($subActivityId);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'internal_actors' => 'nullable|string',
            'external_actors' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'indicators' => 'nullable|string',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'months' => 'nullable|array',
            'status' => 'nullable|in:a_planifier,en_cours,realise,en_retard,replanifie',
            'observations' => 'nullable|string',
        ]);

        $validated['sub_activity_id'] = $subActivity->id;
        $validated['created_by'] = $user->id;

        $action = SmPlanAction::create($validated);
        $action->load(['responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Action ajoutée au Plan SM.',
            'data' => $action,
        ], 201);
    }

    /**
     * Update level 3 Action / Task.
     */
    public function updateAction(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $action = SmPlanAction::whereHas('subActivity.activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'sometimes|required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'internal_actors' => 'nullable|string',
            'external_actors' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'indicators' => 'nullable|string',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'months' => 'nullable|array',
            'status' => 'nullable|in:a_planifier,en_cours,realise,en_retard,replanifie',
            'observations' => 'nullable|string',
        ]);

        $validated['updated_by'] = $user->id;
        $action->update($validated);
        $action->load(['responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Action mise à jour.',
            'data' => $action,
        ]);
    }

    /**
     * Delete level 3 Action.
     */
    public function destroyAction(int $id): JsonResponse
    {
        $user = Auth::user();
        $action = SmPlanAction::whereHas('subActivity.activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($id);

        $action->delete();

        return response()->json([
            'success' => true,
            'message' => 'Action supprimée du Plan SM.',
        ]);
    }

    /**
     * Reschedule past / delayed action (REQ-6.3-08).
     * Authorized for: Assigned responsible, their manager, RQ, or CEO.
     */
    public function rescheduleAction(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $action = SmPlanAction::whereHas('subActivity.activity', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($id);

        // Verification of permission to reschedule (REQ-6.3-08)
        $isResponsible = $action->responsible_id && (int) $action->responsible_id === (int) $user->id;
        $hasPrivilegedRole = $user->hasRole(['admin', 'ceo', 'rq', 'directeur', 'responsable_qualite']);

        if (!$isResponsible && !$hasPrivilegedRole) {
            return response()->json([
                'message' => 'Action non autorisée. Seul le responsable en charge, son N+1, le RQ ou la Direction peuvent replanifier cette action.'
            ], 403);
        }

        $validated = $request->validate([
            'new_deadline' => 'required|date|after:today',
            'reason' => 'required|string|min:5',
        ]);

        $action->update([
            'deadline' => $validated['new_deadline'],
            'status' => 'replanifie',
            'rescheduled_count' => $action->rescheduled_count + 1,
            'rescheduled_reason' => $validated['reason'],
            'rescheduled_by' => $user->id,
            'rescheduled_at' => now(),
            'updated_by' => $user->id,
        ]);

        $action->load(['responsible:id,name', 'rescheduler:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Action replanifiée avec succès.',
            'data' => $action,
        ]);
    }
}

