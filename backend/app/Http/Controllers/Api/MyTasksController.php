<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Audit;
use App\Models\CalibrationPlan;
use App\Models\Communication;
use App\Models\ComplianceObligationAction;
use App\Models\Formation;
use App\Models\MaintenancePlan;
use App\Models\NonConformity;
use App\Models\Objective;
use App\Models\OperationalProjectActivity;
use App\Models\OperationalProjectTask;
use App\Models\PlanAction;
use App\Models\ProcessRiskOpportunity;
use App\Models\Reclamation;
use App\Models\Risk;
use App\Models\StakeholderRequirementAction;
use App\Models\TaskTracking;
use App\Models\TaskTrackingHistory;
use App\Models\User;
use App\Services\WorkingDaysService;
use App\Services\TaskReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MyTasksController extends Controller
{
    protected WorkingDaysService $workingDaysService;
    protected TaskReportService $reportService;

    public function __construct(WorkingDaysService $workingDaysService, TaskReportService $reportService)
    {
        $this->workingDaysService = $workingDaysService;
        $this->reportService = $reportService;
    }

    /**
     * GET /api/my-tasks
     * Aggregate tasks from 14+ sources where user is responsible/organizer/participant/assigned
     * 
     * Query params:
     * - status: non_demarre, en_cours, termine, all (default: all)
     * - type: action, audit, formation, communication, etc. (multi-value: ?type=action&type=formation)
     * - month: YYYY-MM (default: current month)
     * - process_id: filter by process
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $status = $request->query('status', 'all');
        $types = $request->query('type', []);
        $month = $request->query('month', now()->format('Y-m'));
        $processId = $request->query('process_id');

        // Ensure types is array
        if (is_string($types)) {
            $types = [$types];
        }

        $tasks = [];

        // Parse month for date range
        [$startDate, $endDate] = $this->parseMonthRange($month);

        // 1. ACTIONS
        if (empty($types) || in_array('action', $types)) {
            $actionsQuery = Action::query()
                ->with('process:id,title,name')
                ->where(function ($q) use ($user) {
                    $q->where('responsible_id', $user->id);

                    if (Schema::hasColumn('actions', 'pilot_user_ids')) {
                        $q->orWhereJsonContains('pilot_user_ids', $user->id);
                    }

                    if (Schema::hasColumn('actions', 'stakeholders_user_ids')) {
                        $q->orWhereJsonContains('stakeholders_user_ids', $user->id);
                    }
                })
                ->whereBetween('deadline', [$startDate, $endDate]);

            $this->applyEnterpriseFilter($actionsQuery, 'actions', $user->enterprise_id);

            $actions = $actionsQuery->get();

            foreach ($actions as $action) {
                $tasks[] = $this->normalizeTask($action, 'action', $user);
            }
        }

        // 2. AUDITS
        if (empty($types) || in_array('audit', $types)) {
            $auditsQuery = Audit::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('lead_auditor_id', $user->id);
            });
            $this->applyDateRangeFilter($auditsQuery, 'audits', ['scheduled_date', 'date_debut', 'start_date', 'deadline'], $startDate, $endDate);
            $this->applyEnterpriseFilter($auditsQuery, 'audits', $user->enterprise_id);
            $audits = $auditsQuery->get();

            foreach ($audits as $audit) {
                $tasks[] = $this->normalizeTask($audit, 'audit', $user);
            }
        }

        // 3. FORMATIONS
        if (empty($types) || in_array('formation', $types)) {
            $formationsQuery = Formation::where(function ($q) use ($user) {
                $q->where('organizer_user_id', $user->id)
                    ->orWhere('formateur_user_id', $user->id)
                    ->orWhereJsonContains('target_user_ids', $user->id);
            })
                ->whereBetween('date_debut', [$startDate, $endDate]);
            $this->applyEnterpriseFilter($formationsQuery, 'formations', $user->enterprise_id);
            $formations = $formationsQuery->get();

            foreach ($formations as $formation) {
                $tasks[] = $this->normalizeTask($formation, 'formation', $user);
            }
        }

        // 4. COMMUNICATIONS
        if (empty($types) || in_array('communication', $types)) {
            $communicationsQuery = Communication::where(function ($q) use ($user) {
                $q->where('organizer_user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id)
                    ->orWhereJsonContains('participant_user_ids', $user->id);
            })
                ->whereBetween('date_debut', [$startDate, $endDate]);
            $this->applyEnterpriseFilter($communicationsQuery, 'communications', $user->enterprise_id);
            $communications = $communicationsQuery->get();

            foreach ($communications as $communication) {
                $tasks[] = $this->normalizeTask($communication, 'communication', $user);
            }
        }

        // 5. NON-CONFORMITIES
        if (empty($types) || in_array('non_conformity', $types)) {
            $hasInvestigatorUserIds = Schema::hasColumn('non_conformities', 'investigator_user_ids');

            $nonConformitiesQuery = NonConformity::where(function ($q) use ($user, $hasInvestigatorUserIds) {
                $q->where('responsible_id', $user->id);

                if ($hasInvestigatorUserIds) {
                    $q->orWhereJsonContains('investigator_user_ids', $user->id);
                }
            });
            $this->applyDateRangeFilter($nonConformitiesQuery, 'non_conformities', ['deadline', 'due_date', 'end_date', 'date_fin'], $startDate, $endDate);

            $this->applyEnterpriseFilter($nonConformitiesQuery, 'non_conformities', $user->enterprise_id);
            $nonConformities = $nonConformitiesQuery->get();

            foreach ($nonConformities as $nc) {
                $tasks[] = $this->normalizeTask($nc, 'non_conformity', $user);
            }
        }

        // 6. OBJECTIVES
        if (empty($types) || in_array('objective', $types)) {
            $objectivesQuery = Objective::query()
                ->with('process:id,title,name')
                ->where('responsible_id', $user->id);
            $this->applyDateRangeFilter($objectivesQuery, 'objectives', ['deadline', 'due_date', 'end_date', 'date_fin', 'target_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($objectivesQuery, 'objectives', $user->enterprise_id);
            $objectives = $objectivesQuery->get();

            foreach ($objectives as $objective) {
                $tasks[] = $this->normalizeTask($objective, 'objective', $user);
            }
        }

        // 7. RISKS
        if (empty($types) || in_array('risk', $types)) {
            $risksQuery = Risk::query()
                ->with('process:id,title,name')
                ->where(function ($q) use ($user) {
                $q->where('responsible_id', $user->id)
                    ->orWhere('mitigation_responsible_id', $user->id);
            });
            $this->applyDateRangeFilter($risksQuery, 'risks', ['deadline', 'due_date', 'end_date', 'date_fin', 'target_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($risksQuery, 'risks', $user->enterprise_id);
            $risks = $risksQuery->get();

            foreach ($risks as $risk) {
                $tasks[] = $this->normalizeTask($risk, 'risk', $user);
            }
        }

        // 8. PLAN ACTIONS
        if (empty($types) || in_array('plan_action', $types)) {
            $planActionsQuery = PlanAction::query()
                ->with('process:id,title,name')
                ->where('responsible_id', $user->id);
            $this->applyDateRangeFilter($planActionsQuery, 'plan_actions', ['end_date', 'deadline', 'due_date', 'date_fin', 'target_date', 'start_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($planActionsQuery, 'plan_actions', $user->enterprise_id);
            $planActions = $planActionsQuery->get();

            foreach ($planActions as $pa) {
                $tasks[] = $this->normalizeTask($pa, 'plan_action', $user);
            }
        }

        // 9. MAINTENANCE PLANS
        if (empty($types) || in_array('maintenance_plan', $types)) {
            $maintenancePlansQuery = MaintenancePlan::where('responsible_user_id', $user->id);
            $this->applyDateRangeFilter($maintenancePlansQuery, 'maintenance_plans', ['scheduled_date', 'next_maintenance_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($maintenancePlansQuery, 'maintenance_plans', $user->enterprise_id);
            $maintenancePlans = $maintenancePlansQuery->get();

            foreach ($maintenancePlans as $mp) {
                $tasks[] = $this->normalizeTask($mp, 'maintenance_plan', $user);
            }
        }

        // 10. CALIBRATION PLANS
        if (empty($types) || in_array('calibration_plan', $types)) {
            $calibrationPlansQuery = CalibrationPlan::where('responsible_user_id', $user->id);
            $this->applyDateRangeFilter($calibrationPlansQuery, 'calibration_plans', ['scheduled_date', 'next_calibration_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($calibrationPlansQuery, 'calibration_plans', $user->enterprise_id);
            $calibrationPlans = $calibrationPlansQuery->get();

            foreach ($calibrationPlans as $cp) {
                $tasks[] = $this->normalizeTask($cp, 'calibration_plan', $user);
            }
        }

        // 11. COMPLIANCE OBLIGATION ACTIONS
        if (empty($types) || in_array('compliance_obligation_action', $types)) {
            $complianceActionsQuery = ComplianceObligationAction::where('responsible_id', $user->id);
            $this->applyDateRangeFilter($complianceActionsQuery, 'compliance_obligation_actions', ['due_date', 'deadline', 'end_date', 'date_fin', 'target_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($complianceActionsQuery, 'compliance_obligation_actions', $user->enterprise_id);
            $complianceActions = $complianceActionsQuery->get();

            foreach ($complianceActions as $ca) {
                $tasks[] = $this->normalizeTask($ca, 'compliance_obligation_action', $user);
            }
        }

        // 12. STAKEHOLDER REQUIREMENT ACTIONS
        if (empty($types) || in_array('stakeholder_requirement_action', $types)) {
            $stakeholderActionsQuery = StakeholderRequirementAction::where('responsible_id', $user->id);
            $this->applyDateRangeFilter($stakeholderActionsQuery, 'stakeholder_requirement_actions', ['deadline', 'due_date', 'end_date', 'date_fin', 'target_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($stakeholderActionsQuery, 'stakeholder_requirement_actions', $user->enterprise_id);
            $stakeholderActions = $stakeholderActionsQuery->get();

            foreach ($stakeholderActions as $sa) {
                $tasks[] = $this->normalizeTask($sa, 'stakeholder_requirement_action', $user);
            }
        }

        // 13. PROCESS RISK OPPORTUNITIES
        if (empty($types) || in_array('process_risk_opportunity', $types)) {
            $processRisksQuery = ProcessRiskOpportunity::where('responsible_user_id', $user->id)
                ->whereBetween('target_date', [$startDate, $endDate]);
            $this->applyEnterpriseFilter($processRisksQuery, 'process_risks_opportunities', $user->enterprise_id);
            $processRisks = $processRisksQuery->get();

            foreach ($processRisks as $pr) {
                $tasks[] = $this->normalizeTask($pr, 'process_risk_opportunity', $user);
            }
        }

        // 14. OPERATIONAL PROJECT ACTIVITIES
        if (empty($types) || in_array('operational_project_activity', $types)) {
            $projectActivitiesQuery = OperationalProjectActivity::where(function ($q) use ($user) {
                $q->where('responsible_user_id', $user->id)
                    ->orWhereJsonContains('assigned_user_ids', $user->id);
            });
            $this->applyDateRangeFilter($projectActivitiesQuery, 'operational_project_activities', ['start_date', 'due_date', 'deadline', 'date_debut', 'end_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($projectActivitiesQuery, 'operational_project_activities', $user->enterprise_id);
            $projectActivities = $projectActivitiesQuery->get();

            foreach ($projectActivities as $pa) {
                $tasks[] = $this->normalizeTask($pa, 'operational_project_activity', $user);
            }
        }

        // 15. OPERATIONAL PROJECT TASKS
        if (empty($types) || in_array('operational_project_task', $types)) {
            $projectTasksQuery = OperationalProjectTask::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereJsonContains('assigned_user_ids', $user->id);
            });
            $this->applyDateRangeFilter($projectTasksQuery, 'operational_project_tasks', ['start_date', 'due_date', 'deadline', 'date_debut', 'end_date'], $startDate, $endDate);
            $this->applyEnterpriseFilter($projectTasksQuery, 'operational_project_tasks', $user->enterprise_id);
            $projectTasks = $projectTasksQuery->get();

            foreach ($projectTasks as $pt) {
                $tasks[] = $this->normalizeTask($pt, 'operational_project_task', $user);
            }
        }

        // 16. RECLAMATIONS / INCIDENTS
        if (empty($types) || in_array('reclamation', $types)) {
            $reclamationsQuery = Reclamation::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('user_id', $user->id);
            });
            $this->applyDateRangeFilter($reclamationsQuery, 'reclamations', ['due_date', 'received_date', 'closed_date'], $startDate, $endDate);
            $reclamations = $reclamationsQuery->get();

            foreach ($reclamations as $reclamation) {
                $tasks[] = $this->normalizeTask($reclamation, 'reclamation', $user);
            }
        }

        // Filter by status if provided
        if ($status !== 'all') {
            $tasks = array_filter($tasks, function ($task) use ($status) {
                return $task['status'] === $status;
            });
        }

        // Filter by process_id if provided
        if ($processId) {
            $tasks = array_filter($tasks, function ($task) use ($processId) {
                return $task['process_id'] === (int)$processId;
            });
        }

        // Sort by deadline
        usort($tasks, function ($a, $b) {
            $aDate = strtotime($a['deadline'] ?? $a['start_date'] ?? now());
            $bDate = strtotime($b['deadline'] ?? $b['start_date'] ?? now());
            return $aDate - $bDate;
        });

        return response()->json([
            'success' => true,
            'data' => array_values($tasks),
            'total' => count($tasks),
        ]);
    }

    /**
     * POST /api/my-tasks/{type}/{id}/tracking
     * Create or update task tracking entry
     */
    public function tracking(Request $request, string $type, int $id): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'status' => 'required|in:non_demarre,en_cours,termine',
            'progress_rate' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        // Validate status-to-rate mapping
        if ($validated['status'] === 'non_demarre' && $validated['progress_rate'] !== 0) {
            return response()->json(['error' => 'Status "non_demarre" requires progress_rate = 0'], 422);
        }
        if ($validated['status'] === 'termine' && $validated['progress_rate'] !== 100) {
            return response()->json(['error' => 'Status "termine" requires progress_rate = 100'], 422);
        }
        if ($validated['status'] === 'en_cours' && ($validated['progress_rate'] < 1 || $validated['progress_rate'] > 99)) {
            return response()->json(['error' => 'Status "en_cours" requires progress_rate between 1 and 99'], 422);
        }

        // Map type to model class
        $modelClass = $this->mapTypeToModel($type);
        if (!$modelClass) {
            return response()->json(['error' => 'Invalid task type'], 422);
        }

        // Find the task
        $task = $modelClass::findOrFail($id);

        // Check authorization
        if (!$this->userCanTrackTask($user, $task, $type)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if in activation window
        $trackingAvailable = $this->isTrackingAvailable($task, $user);
        if (!$trackingAvailable) {
            return response()->json(['error' => 'Task is outside activation window'], 422);
        }

        // Find or create TaskTracking entry
        $tracking = TaskTracking::firstOrCreate(
            [
                'user_id' => $user->id,
                'trackable_type' => $modelClass,
                'trackable_id' => $id,
            ],
            [
                'status' => $validated['status'],
                'progress_rate' => $validated['progress_rate'],
                'notes' => $validated['notes'] ?? null,
                'tracked_at' => now(),
            ]
        );

        // If exists, update tracking (history is handled by TaskTrackingObserver)
        if ($tracking->wasRecentlyCreated === false) {
            $tracking->update([
                'status' => $validated['status'],
                'progress_rate' => $validated['progress_rate'],
                'notes' => $validated['notes'] ?? null,
                'tracked_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $tracking,
        ]);
    }

    /**
     * GET /api/my-tasks/{type}/{id}/history
     * Get tracking history for a task
     */
    public function history(string $type, int $id): JsonResponse
    {
        $user = Auth::user();
        $modelClass = $this->mapTypeToModel($type);

        if (!$modelClass) {
            return response()->json(['error' => 'Invalid task type'], 422);
        }

        $tracking = TaskTracking::where([
            'user_id' => $user->id,
            'trackable_type' => $modelClass,
            'trackable_id' => $id,
        ])->firstOrFail();

        $history = TaskTrackingHistory::where('task_tracking_id', $tracking->id)
            ->orderBy('changed_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'changed_at' => $entry->changed_at,
                    'changed_by' => $entry->changedBy?->name,
                    'status_old' => $entry->status_old,
                    'status_new' => $entry->status_new,
                    'rate_old' => $entry->progress_rate_old,
                    'rate_new' => $entry->progress_rate_new,
                    'notes' => $entry->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * GET /api/my-tasks/{type}/{id}/report
     * Generate and download task report (.docx)
     */
    public function report(string $type, int $id): Response|BinaryFileResponse
    {
        $user = Auth::user();
        $modelClass = $this->mapTypeToModel($type);

        if (!$modelClass) {
            return response('Invalid task type', 422);
        }

        // Find the task
        $task = $modelClass::findOrFail($id);

        // Check authorization
        if (!$this->userCanTrackTask($user, $task, $type)) {
            return response('Unauthorized', 403);
        }

        // Generate report
        try {
            $path = $this->reportService->generateReport($task, $type, $user);

            // Download file
            return response()->download(
                storage_path("app/{$path}"),
                "rapport-{$type}-{$task->id}.docx"
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            report($e);
            return response('Error generating report', 500);
        }
    }

    /**
     * Normalize task from any source to uniform format
     */
    private function normalizeTask(object $task, string $type, User $user): array
    {
        $tracking = TaskTracking::where([
            'user_id' => $user->id,
            'trackable_type' => $this->mapTypeToModel($type),
            'trackable_id' => $task->id,
        ])->first();

        $trackingAvailable = $this->isTrackingAvailable($task, $user);

        // Determine responsible user
        $responsibleId = $this->getResponsibleUserId($task, $type);
        $responsible = $responsibleId ? User::find($responsibleId)?->name : null;

        // Extract dates
        $startDate = $task->start_date ?? $task->date_debut ?? $task->scheduled_date ?? null;
        $endDate = $task->end_date ?? $task->date_fin ?? $task->completion_date ?? null;
        $deadline = $task->deadline ?? $startDate;

        // Determine status
        $status = $tracking?->status ?? 'non_demarre';
        $progressRate = $tracking?->progress_rate ?? 0;
        $processName = $task->process?->name
            ?? $task->process?->title
            ?? $task->process_name
            ?? null;

        return [
            'id' => $task->id,
            'type' => $type,
            'title' => $task->title ?? $task->name ?? $task->description ?? 'Untitled',
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
            'deadline' => $deadline?->format('Y-m-d'),
            'status' => $status,
            'progress_rate' => $progressRate,
            'responsible_id' => $responsibleId,
            'responsible' => $responsible,
            'process_id' => $task->process_id ?? null,
            'process_name' => $processName,
            'tracking_available' => $trackingAvailable,
            'has_tracking' => $tracking !== null,
            'frequency' => $task->frequency ?? null,
        ];
    }

    /**
     * Get responsible user ID based on task type
     */
    private function getResponsibleUserId(object $task, string $type): ?int
    {
        return match ($type) {
            'action' => $task->responsible_id,
            'audit' => $task->assigned_to ?? $task->lead_auditor_id,
            'formation' => $task->organizer_user_id,
            'communication' => $task->organizer_user_id,
            'non_conformity' => $task->responsible_id,
            'objective' => $task->responsible_id,
            'risk' => $task->responsible_id,
            'plan_action' => $task->responsible_id,
            'maintenance_plan' => $task->responsible_user_id,
            'calibration_plan' => $task->responsible_user_id,
            'compliance_obligation_action' => $task->responsible_id,
            'stakeholder_requirement_action' => $task->responsible_id,
            'process_risk_opportunity' => $task->responsible_user_id,
            'operational_project_activity' => $task->responsible_user_id,
            'operational_project_task' => $task->responsible_user_id ?? $task->assigned_to,
            'reclamation' => $task->assigned_to ?? $task->user_id,
            default => null,
        };
    }

    /**
     * Map type string to model class
     */
    private function mapTypeToModel(string $type): ?string
    {
        return match ($type) {
            'action' => Action::class,
            'audit' => Audit::class,
            'formation' => Formation::class,
            'communication' => Communication::class,
            'non_conformity' => NonConformity::class,
            'objective' => Objective::class,
            'risk' => Risk::class,
            'plan_action' => PlanAction::class,
            'maintenance_plan' => MaintenancePlan::class,
            'calibration_plan' => CalibrationPlan::class,
            'compliance_obligation_action' => ComplianceObligationAction::class,
            'stakeholder_requirement_action' => StakeholderRequirementAction::class,
            'process_risk_opportunity' => ProcessRiskOpportunity::class,
            'operational_project_activity' => OperationalProjectActivity::class,
            'operational_project_task' => OperationalProjectTask::class,
            'reclamation' => Reclamation::class,
            default => null,
        };
    }

    /**
     * Check if user can track this task
     */
    private function userCanTrackTask(User $user, object $task, string $type): bool
    {
        $responsibleId = $this->getResponsibleUserId($task, $type);

        // User must be responsible for the task (for now)
        return $user->id === $responsibleId;
    }

    /**
     * Check if task is within activation window (WorkingDaysService)
     */
    private function isTrackingAvailable(object $task, User $user): bool
    {
        $deadline = $task->deadline ?? $task->date_fin ?? $task->date_debut ?? $task->start_date ?? $task->scheduled_date;

        if (!$deadline) {
            return false;
        }

        // Get frequency
        $frequency = $task->frequency ?? 'ponctuelle';

        return $this->workingDaysService->isInWindow(
            \Carbon\Carbon::parse($deadline),
            $frequency,
            \Carbon\Carbon::today(),
            (int) ($user->enterprise_id ?? 0)
        );
    }

    /**
     * Parse month string to date range
     */
    private function parseMonthRange(string $month): array
    {
        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $month);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            return [
                $start,
                $end,
            ];
        } catch (\Exception $e) {
            return [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ];
        }
    }

    private function applyEnterpriseFilter(Builder $query, string $table, mixed $enterpriseId): void
    {
        if (!empty($enterpriseId) && Schema::hasColumn($table, 'enterprise_id')) {
            $query->where('enterprise_id', $enterpriseId);
        }
    }

    private function applyDateRangeFilter(Builder $query, string $table, array $candidates, mixed $startDate, mixed $endDate): void
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                $query->whereBetween($column, [$startDate, $endDate]);
                return;
            }
        }
    }

}
