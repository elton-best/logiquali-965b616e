<?php

namespace App\Modules\Planning\Controllers;

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
use App\Models\Process;
use App\Models\ProcessRiskOpportunity;
use App\Models\Risk;
use App\Models\StakeholderRequirementAction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PlanSMController extends Controller
{
    /**
     * GET /api/plan-sm
     * Global calendar view: aggregate tasks from all users on the site (no user_id filter)
     * 
     * Query params:
     * - view: day|week|month (default: month)
     * - start_date: YYYY-MM-DD (optional, override view calculation)
     * - end_date: YYYY-MM-DD (optional, override view calculation)
     * - user_id: filter by responsible user (optional, multi: ?user_id=1&user_id=2)
     * - process_id: filter by process (optional)
     * - type: filter by task type (optional, multi: ?type=action&type=formation)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isGlobalViewer = $user->hasPermissionTo('view_all_tasks');
        $pilotOrCopilotProcessIds = Process::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->where(function ($q) use ($user) {
                $q->where('pilot_id', $user->id)
                    ->orWhere('copilot_id', $user->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Authorization: global permission OR process pilot/copilot
        if (!$isGlobalViewer && empty($pilotOrCopilotProcessIds)) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }

        // View mode & date range
        $view = $request->query('view', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // If start_date/end_date not provided, calculate from view
        if (!$startDate || !$endDate) {
            [$startDate, $endDate] = $this->calculateDateRange($view);
        } else {
            $startDate = \Carbon\Carbon::parse($startDate);
            $endDate = \Carbon\Carbon::parse($endDate);
        }

        // Filters
        $userIds = $request->query('user_id', []);
        if (is_string($userIds)) {
            $userIds = [$userIds];
        }
        $userIds = array_map('intval', array_filter($userIds));

        $processId = $request->query('process_id');
        $types = $request->query('type', []);
        if (is_string($types)) {
            $types = [$types];
        }

        $tasks = [];

        // 1. ACTIONS
        if (empty($types) || in_array('action', $types)) {
            $query = Action::query();
            $this->applyEnterpriseFilter($query, 'actions', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'actions', ['deadline', 'due_date', 'date_fin', 'end_date', 'target_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_id', $userIds);
            }

            $actions = $query->get();
            foreach ($actions as $action) {
                $tasks[] = $this->normalizeTask($action, 'action');
            }
        }

        // 2. AUDITS
        if (empty($types) || in_array('audit', $types)) {
            $query = Audit::query();
            $this->applyEnterpriseFilter($query, 'audits', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'audits', ['scheduled_date', 'date_debut', 'start_date', 'deadline'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('assigned_to', $userIds)
                        ->orWhereIn('lead_auditor_id', $userIds);
                });
            }

            $audits = $query->get();
            foreach ($audits as $audit) {
                $tasks[] = $this->normalizeTask($audit, 'audit');
            }
        }

        // 3. FORMATIONS
        if (empty($types) || in_array('formation', $types)) {
            $query = Formation::where('enterprise_id', $user->enterprise_id)
                ->whereBetween('date_debut', [$startDate, $endDate]);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('organizer_user_id', $userIds)
                        ->orWhereIn('formateur_user_id', $userIds);
                    foreach ($userIds as $id) {
                        $q->orWhereJsonContains('target_user_ids', $id);
                    }
                });
            }

            $formations = $query->get();
            foreach ($formations as $formation) {
                $tasks[] = $this->normalizeTask($formation, 'formation');
            }
        }

        // 4. COMMUNICATIONS
        if (empty($types) || in_array('communication', $types)) {
            $query = Communication::where('enterprise_id', $user->enterprise_id)
                ->whereBetween('date_debut', [$startDate, $endDate]);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('organizer_user_id', $userIds)
                        ->orWhereIn('responsible_user_id', $userIds);
                    foreach ($userIds as $id) {
                        $q->orWhereJsonContains('participant_user_ids', $id);
                    }
                });
            }

            $communications = $query->get();
            foreach ($communications as $communication) {
                $tasks[] = $this->normalizeTask($communication, 'communication');
            }
        }

        // 5. NON-CONFORMITIES
        if (empty($types) || in_array('non_conformity', $types)) {
            $query = NonConformity::query();
            $this->applyEnterpriseFilter($query, 'non_conformities', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'non_conformities', ['deadline', 'due_date', 'date_fin', 'end_date'], $startDate, $endDate);
            $hasInvestigatorUserIds = Schema::hasColumn('non_conformities', 'investigator_user_ids');

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds, $hasInvestigatorUserIds) {
                    $q->whereIn('responsible_id', $userIds);

                    if ($hasInvestigatorUserIds) {
                        foreach ($userIds as $id) {
                            $q->orWhereJsonContains('investigator_user_ids', $id);
                        }
                    }
                });
            }

            $nonConformities = $query->get();
            foreach ($nonConformities as $nc) {
                $tasks[] = $this->normalizeTask($nc, 'non_conformity');
            }
        }

        // 6. OBJECTIVES
        if (empty($types) || in_array('objective', $types)) {
            $query = Objective::query();
            $this->applyEnterpriseFilter($query, 'objectives', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'objectives', ['deadline', 'due_date', 'date_fin', 'end_date', 'target_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_id', $userIds);
            }

            $objectives = $query->get();
            foreach ($objectives as $objective) {
                $tasks[] = $this->normalizeTask($objective, 'objective');
            }
        }

        // 7. RISKS
        if (empty($types) || in_array('risk', $types)) {
            $query = Risk::query();
            $this->applyEnterpriseFilter($query, 'risks', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'risks', ['deadline', 'due_date', 'date_fin', 'end_date', 'target_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('responsible_id', $userIds)
                        ->orWhereIn('mitigation_responsible_id', $userIds);
                });
            }

            $risks = $query->get();
            foreach ($risks as $risk) {
                $tasks[] = $this->normalizeTask($risk, 'risk');
            }
        }

        // 8. PLAN ACTIONS
        if (empty($types) || in_array('plan_action', $types)) {
            $query = PlanAction::query();
            $this->applyEnterpriseFilter($query, 'plan_actions', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'plan_actions', ['end_date', 'deadline', 'due_date', 'date_fin', 'target_date', 'start_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_id', $userIds);
            }

            $planActions = $query->get();
            foreach ($planActions as $pa) {
                $tasks[] = $this->normalizeTask($pa, 'plan_action');
            }
        }

        // 9. MAINTENANCE PLANS
        if (empty($types) || in_array('maintenance_plan', $types)) {
            $query = MaintenancePlan::query();
            $this->applyEnterpriseFilter($query, 'maintenance_plans', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'maintenance_plans', ['scheduled_date', 'date_debut', 'start_date', 'deadline'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_user_id', $userIds);
            }

            $maintenancePlans = $query->get();
            foreach ($maintenancePlans as $mp) {
                $tasks[] = $this->normalizeTask($mp, 'maintenance_plan');
            }
        }

        // 10. CALIBRATION PLANS
        if (empty($types) || in_array('calibration_plan', $types)) {
            $query = CalibrationPlan::query();
            $this->applyEnterpriseFilter($query, 'calibration_plans', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'calibration_plans', ['scheduled_date', 'date_debut', 'start_date', 'deadline'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_user_id', $userIds);
            }

            $calibrationPlans = $query->get();
            foreach ($calibrationPlans as $cp) {
                $tasks[] = $this->normalizeTask($cp, 'calibration_plan');
            }
        }

        // 11. COMPLIANCE OBLIGATION ACTIONS
        if (empty($types) || in_array('compliance_obligation_action', $types)) {
            $query = ComplianceObligationAction::query();
            $this->applyEnterpriseFilter($query, 'compliance_obligation_actions', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'compliance_obligation_actions', ['due_date', 'deadline', 'date_fin', 'end_date', 'target_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_id', $userIds);
            }

            $complianceActions = $query->get();
            foreach ($complianceActions as $ca) {
                $tasks[] = $this->normalizeTask($ca, 'compliance_obligation_action');
            }
        }

        // 12. STAKEHOLDER REQUIREMENT ACTIONS
        if (empty($types) || in_array('stakeholder_requirement_action', $types)) {
            $query = StakeholderRequirementAction::query();
            $this->applyEnterpriseFilter($query, 'stakeholder_requirement_actions', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'stakeholder_requirement_actions', ['deadline', 'due_date', 'date_fin', 'end_date', 'target_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_id', $userIds);
            }

            $stakeholderActions = $query->get();
            foreach ($stakeholderActions as $sa) {
                $tasks[] = $this->normalizeTask($sa, 'stakeholder_requirement_action');
            }
        }

        // 13. PROCESS RISK OPPORTUNITIES
        if (empty($types) || in_array('process_risk_opportunity', $types)) {
            $query = ProcessRiskOpportunity::query();
            $this->applyEnterpriseFilter($query, 'process_risks_opportunities', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'process_risks_opportunities', ['target_date', 'deadline', 'due_date', 'date_fin', 'end_date'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->whereIn('responsible_user_id', $userIds);
            }

            $processRisks = $query->get();
            foreach ($processRisks as $pr) {
                $tasks[] = $this->normalizeTask($pr, 'process_risk_opportunity');
            }
        }

        // 14. OPERATIONAL PROJECT ACTIVITIES
        if (empty($types) || in_array('operational_project_activity', $types)) {
            $query = OperationalProjectActivity::query();
            $this->applyEnterpriseFilter($query, 'operational_project_activities', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'operational_project_activities', ['start_date', 'date_debut', 'scheduled_date', 'deadline'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('responsible_user_id', $userIds);
                    foreach ($userIds as $id) {
                        $q->orWhereJsonContains('assigned_user_ids', $id);
                    }
                });
            }

            $projectActivities = $query->get();
            foreach ($projectActivities as $pa) {
                $tasks[] = $this->normalizeTask($pa, 'operational_project_activity');
            }
        }

        // 15. OPERATIONAL PROJECT TASKS
        if (empty($types) || in_array('operational_project_task', $types)) {
            $query = OperationalProjectTask::query();
            $this->applyEnterpriseFilter($query, 'operational_project_tasks', $user->enterprise_id);
            $this->applyDateRangeFilter($query, 'operational_project_tasks', ['start_date', 'date_debut', 'scheduled_date', 'deadline'], $startDate, $endDate);

            if (!empty($userIds)) {
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('assigned_to', $userIds);
                    foreach ($userIds as $id) {
                        $q->orWhereJsonContains('assigned_user_ids', $id);
                    }
                });
            }

            $projectTasks = $query->get();
            foreach ($projectTasks as $pt) {
                $tasks[] = $this->normalizeTask($pt, 'operational_project_task');
            }
        }

        // Filter by process_id if provided
        if ($processId) {
            $tasks = array_filter($tasks, function ($task) use ($processId) {
                return $task['process_id'] === (int)$processId;
            });
        }

        // Process pilot/copilot can only see tasks linked to their process scope.
        if (!$isGlobalViewer) {
            $tasks = array_filter($tasks, function ($task) use ($pilotOrCopilotProcessIds) {
                $taskProcessId = (int) ($task['process_id'] ?? 0);
                return $taskProcessId > 0 && in_array($taskProcessId, $pilotOrCopilotProcessIds, true);
            });
        }

        // Sort by start date
        usort($tasks, function ($a, $b) {
            $aDate = strtotime($a['start_date'] ?? $a['deadline'] ?? now());
            $bDate = strtotime($b['start_date'] ?? $b['deadline'] ?? now());
            return $aDate - $bDate;
        });

        // Paginate
        $page = max(1, (int)$request->query('page', 1));
        $perPage = max(1, min((int)$request->query('per_page', 50), 300));
        $total = count($tasks);
        $tasks = array_slice($tasks, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'data' => $tasks,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
            'filters' => [
                'view' => $view,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'user_ids' => $userIds,
                'process_id' => $processId,
                'types' => $types,
            ],
        ]);
    }

    /**
     * Normalize a task from any model to calendar format
     */
    private function normalizeTask($model, string $type): array
    {
        $responsibleId = null;
        $responsibleName = null;
        $involvedPeople = [];
        $processId = null;
        $frequency = 'ponctuelle';
        $color = $this->getColorByType($type);
        $startDate = null;
        $deadline = null;
        $status = $model->status ?? 'en_cours';

        switch ($type) {
            case 'action':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $involvedPeople = array_filter([
                    ...(is_array($model->pilot_user_ids) ? $model->pilot_user_ids : []),
                    ...(is_array($model->stakeholders_user_ids) ? $model->stakeholders_user_ids : []),
                ]);
                $processId = $model->process_id;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'audit':
                $responsibleId = $model->lead_auditor_id ?? $model->assigned_to;
                $responsibleName = $model->leadAuditor?->name ?? $model->assignedTo?->name;
                $startDate = $model->scheduled_date?->toDateString();
                $deadline = $model->scheduled_date?->toDateString();
                break;

            case 'formation':
                $responsibleId = $model->organizer_user_id;
                $responsibleName = $model->organizer?->name;
                $involvedPeople = array_filter([
                    $model->formateur_user_id,
                    ...(is_array($model->target_user_ids) ? $model->target_user_ids : []),
                ]);
                $processId = $model->process_id;
                $frequency = $model->frequency ?? 'ponctuelle';
                $startDate = $model->date_debut?->toDateString();
                $deadline = $model->date_fin?->toDateString();
                break;

            case 'communication':
                $responsibleId = $model->organizer_user_id;
                $responsibleName = $model->organizer?->name;
                $involvedPeople = array_filter([
                    $model->responsible_user_id,
                    ...(is_array($model->participant_user_ids) ? $model->participant_user_ids : []),
                ]);
                $processId = $model->process_id;
                $frequency = $model->frequency ?? 'ponctuelle';
                $startDate = $model->date_debut?->toDateString();
                $deadline = $model->date_fin?->toDateString();
                break;

            case 'non_conformity':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $involvedPeople = is_array($model->investigator_user_ids) ? $model->investigator_user_ids : [];
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'objective':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $processId = $model->process_id;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'risk':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'plan_action':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'maintenance_plan':
                $responsibleId = $model->responsible_user_id;
                $responsibleName = $model->responsibleUser?->name;
                $frequency = $model->frequency ?? 'mensuelle';
                $startDate = $model->scheduled_date?->toDateString();
                $deadline = $model->scheduled_date?->toDateString();
                break;

            case 'calibration_plan':
                $responsibleId = $model->responsible_user_id;
                $responsibleName = $model->responsibleUser?->name;
                $frequency = $model->frequency ?? 'annuelle';
                $startDate = $model->scheduled_date?->toDateString();
                $deadline = $model->scheduled_date?->toDateString();
                break;

            case 'compliance_obligation_action':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'stakeholder_requirement_action':
                $responsibleId = $model->responsible_id;
                $responsibleName = $model->responsible?->name;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'process_risk_opportunity':
                $responsibleId = $model->responsible_user_id;
                $responsibleName = $model->responsibleUser?->name;
                $processId = $model->process_id;
                $startDate = $model->created_at?->toDateString();
                $deadline = $model->deadline?->toDateString();
                break;

            case 'operational_project_activity':
                $responsibleId = $model->responsible_user_id;
                $responsibleName = $model->responsibleUser?->name;
                $involvedPeople = is_array($model->assigned_user_ids) ? $model->assigned_user_ids : [];
                $startDate = $model->start_date?->toDateString();
                $deadline = $model->end_date?->toDateString();
                break;

            case 'operational_project_task':
                $responsibleId = $model->assigned_to;
                $responsibleName = $model->assignedTo?->name;
                $involvedPeople = is_array($model->assigned_user_ids) ? $model->assigned_user_ids : [];
                $startDate = $model->start_date?->toDateString();
                $deadline = $model->end_date?->toDateString();
                break;
        }

        return [
            'id' => "{$model->id}-{$type}",
            'type' => $type,
            'title' => $model->title ?? $model->name ?? 'N/A',
            'start_date' => $startDate ?? now()->toDateString(),
            'deadline' => $deadline ?? now()->toDateString(),
            'status' => $status,
            'responsible_id' => $responsibleId,
            'responsible_name' => $responsibleName,
            'involved_people' => array_unique(array_filter($involvedPeople)),
            'frequency' => $frequency,
            'process_id' => $processId,
            'color' => $color,
        ];
    }

    private function getColorByType(string $type): string
    {
        $colors = [
            'action' => '#FF5252',                  // Red
            'audit' => '#FF9800',                   // Orange
            'formation' => '#2196F3',               // Blue
            'communication' => '#FFC107',           // Yellow
            'non_conformity' => '#E91E63',          // Pink
            'objective' => '#00BCD4',               // Cyan
            'risk' => '#FF6F00',                    // Dark Orange
            'plan_action' => '#8BC34A',             // Light Green
            'maintenance_plan' => '#9C27B0',        // Purple
            'calibration_plan' => '#4CAF50',        // Green
            'compliance_obligation_action' => '#673AB7', // Deep Purple
            'stakeholder_requirement_action' => '#009688', // Teal
            'process_risk_opportunity' => '#F57F17', // Brown
            'operational_project_activity' => '#3F51B5', // Indigo
            'operational_project_task' => '#512DA8', // Deep Purple
        ];

        return $colors[$type] ?? '#9E9E9E'; // Gray fallback
    }

    private function calculateDateRange(string $view): array
    {
        $today = now();

        return match ($view) {
            'day' => [
                $today->clone()->startOfDay(),
                $today->clone()->endOfDay(),
            ],
            'week' => [
                $today->clone()->startOfWeek(),
                $today->clone()->endOfWeek(),
            ],
            'month' => [
                $today->clone()->startOfMonth(),
                $today->clone()->endOfMonth(),
            ],
            default => [
                $today->clone()->startOfMonth(),
                $today->clone()->endOfMonth(),
            ],
        };
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
