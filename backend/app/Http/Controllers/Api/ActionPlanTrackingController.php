<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\TaskTracking;
use App\Models\TaskTrackingHistory;
use App\Models\User;
use App\Services\WorkingDaysService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionPlanTrackingController extends Controller
{
    public function __construct(private readonly WorkingDaysService $workingDaysService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $siteId = (int) ($request->query('site_id') ?: $user?->site_id ?: 0);

        $query = Action::query()->with(['responsible:id,name']);
        if ($user?->enterprise_id) {
            $query->where('enterprise_id', $user->enterprise_id);
        }
        if ($siteId > 0) {
            $query->where('site_id', $siteId);
        }

        $actions = $query->orderByDesc('updated_at')->limit(300)->get();

        $rows = $actions->map(function (Action $action) use ($user) {
            $tracking = TaskTracking::query()
                ->where('trackable_type', Action::class)
                ->where('trackable_id', $action->id)
                ->latest('tracked_at')
                ->first();

            $canVerify = $this->hasPermission($user, 'verify_actions');
            $isActor = (int) $action->responsible_id === (int) $user->id;
            $locked = $tracking !== null && !$canVerify;
            $windowOpen = $this->workingDaysService->isInWindow(
                $action->deadline ? Carbon::parse($action->deadline) : null,
                'ponctuelle',
                now(),
                (int) ($user?->enterprise_id ?? 0) ?: null
            );

            if (!$canVerify && $tracking && (int) $tracking->user_id !== (int) $user->id) {
                return null;
            }

            return [
                'id' => "action-{$action->id}",
                'type' => 'action',
                'source_id' => $action->id,
                'related_activity' => $action->title,
                'start_date' => optional($action->start_date)->format('Y-m-d'),
                'end_date' => optional($action->deadline)->format('Y-m-d'),
                'responsible_name' => $action->responsible?->name,
                'involved_people' => null,
                'recurrence' => 'one_time',
                'tracking' => $tracking ? [
                    'id' => $tracking->id,
                    'status' => $tracking->status,
                    'progress_rate' => $tracking->progress_rate,
                    'notes' => $tracking->notes,
                    'user_id' => $tracking->user_id,
                    'tracked_at' => optional($tracking->tracked_at)->toISOString(),
                ] : null,
                'can_track' => $isActor || $canVerify,
                'window_open' => $windowOpen,
                'is_locked' => $locked,
                'is_verifier' => $canVerify,
            ];
        })->filter()->values();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        if ($type !== 'action') {
            return response()->json(['message' => 'Type non supporté'], 422);
        }

        $action = Action::query()->findOrFail($id);
        $this->assertSameEnterprise($user, $action->enterprise_id);

        $canVerify = $this->hasPermission($user, 'verify_actions');
        $isActor = (int) $action->responsible_id === (int) $user->id;
        if (!$isActor && !$canVerify) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:non_demarre,en_cours,termine',
            'progress_rate' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);

        $this->validateStatusRate($validated['status'], (int) $validated['progress_rate']);

        $existing = TaskTracking::query()
            ->where('trackable_type', Action::class)
            ->where('trackable_id', $action->id)
            ->latest('tracked_at')
            ->first();

        if ($existing && !$canVerify) {
            return response()->json(['message' => 'Suivi déjà enregistré et verrouillé.'], 409);
        }

        if ($existing && $canVerify) {
            $oldStatus = $existing->status;
            $oldRate = $existing->progress_rate;
            $existing->update([
                'status' => $validated['status'],
                'progress_rate' => (int) $validated['progress_rate'],
                'notes' => $validated['notes'] ?? null,
                'tracked_at' => now(),
            ]);

            TaskTrackingHistory::create([
                'task_tracking_id' => $existing->id,
                'status_old' => $oldStatus,
                'status_new' => $existing->status,
                'progress_rate_old' => $oldRate,
                'progress_rate_new' => $existing->progress_rate,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            return response()->json(['data' => $existing]);
        }

        $tracking = TaskTracking::create([
            'user_id' => $user->id,
            'trackable_type' => Action::class,
            'trackable_id' => $action->id,
            'status' => $validated['status'],
            'progress_rate' => (int) $validated['progress_rate'],
            'notes' => $validated['notes'] ?? null,
            'tracked_at' => now(),
        ]);

        return response()->json(['data' => $tracking], 201);
    }

    public function verifyUpdate(Request $request, int $trackingId): JsonResponse
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'verify_actions')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:non_demarre,en_cours,termine',
            'progress_rate' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
            'verifier_comment' => 'required|string|max:5000',
        ]);
        $this->validateStatusRate($validated['status'], (int) $validated['progress_rate']);

        $tracking = TaskTracking::query()->findOrFail($trackingId);
        $oldStatus = $tracking->status;
        $oldRate = $tracking->progress_rate;

        $mergedNotes = trim((string) ($tracking->notes ?? ''));
        $commentLine = '[Vérification] '.$validated['verifier_comment'];
        $notes = $mergedNotes === '' ? $commentLine : ($mergedNotes."\n".$commentLine);

        $tracking->update([
            'status' => $validated['status'],
            'progress_rate' => (int) $validated['progress_rate'],
            'notes' => $notes,
            'tracked_at' => now(),
        ]);

        TaskTrackingHistory::create([
            'task_tracking_id' => $tracking->id,
            'status_old' => $oldStatus,
            'status_new' => $tracking->status,
            'progress_rate_old' => $oldRate,
            'progress_rate_new' => $tracking->progress_rate,
            'changed_by' => $user->id,
            'changed_at' => now(),
        ]);

        return response()->json(['data' => $tracking]);
    }

    public function updateDeadline(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'verify_actions')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        if ($type !== 'action') {
            return response()->json(['message' => 'Type non supporté'], 422);
        }

        $validated = $request->validate([
            'deadline' => 'required|date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $action = Action::query()->findOrFail($id);
        $this->assertSameEnterprise($user, $action->enterprise_id);

        $action->deadline = Carbon::parse($validated['deadline']);
        $action->save();

        return response()->json([
            'message' => 'Date de fin mise à jour.',
            'data' => [
                'id' => $action->id,
                'deadline' => optional($action->deadline)->format('Y-m-d'),
            ],
        ]);
    }

    private function validateStatusRate(string $status, int $rate): void
    {
        if ($status === 'non_demarre' && $rate !== 0) {
            abort(422, 'Pour le statut non démarré, le taux doit être 0.');
        }
        if ($status === 'termine' && $rate !== 100) {
            abort(422, 'Pour le statut terminé, le taux doit être 100.');
        }
        if ($status === 'en_cours' && ($rate < 1 || $rate > 99)) {
            abort(422, 'Pour le statut en cours, le taux doit être entre 1 et 99.');
        }
    }

    private function hasPermission($user, string $permission): bool
    {
        if (!$user) return false;
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return true;
        }
        $list = array_merge(
            is_array($user->permissions ?? null) ? $user->permissions : [],
            is_array($user->effective_permissions ?? null) ? $user->effective_permissions : []
        );
        foreach ($list as $p) {
            $name = is_string($p) ? $p : ($p['name'] ?? null);
            if ($name === $permission) return true;
        }
        return false;
    }

    private function assertSameEnterprise($user, ?int $enterpriseId): void
    {
        if ((int) ($user->enterprise_id ?? 0) !== (int) ($enterpriseId ?? 0)) {
            abort(403, 'Accès refusé');
        }
    }
}
