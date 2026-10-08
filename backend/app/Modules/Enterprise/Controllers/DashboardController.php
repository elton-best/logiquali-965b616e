<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Models\UserDashboardLayout;
use App\Models\Dashboard;
use App\Models\Site;
use App\Models\User;
use App\Models\Process;
use App\Models\Risk;
use App\Models\Action;
use App\Models\Audit;
use App\Models\NonConformity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Résout le scope dashboard en fonction du rôle:
     * - site_manager (non admin_entreprise): scope site forcé
     * - admin_entreprise: scope enterprise par défaut, site si demandé
     */
    private function resolveDashboardScope(Request $request): array
    {
        $user = $request->user();
        $enterpriseId = (int) ($user->enterprise_id ?? 0);

        if (!$enterpriseId) {
            return [
                'error' => response()->json([
                    'success' => false,
                    'message' => 'User not associated with an enterprise',
                ], 403),
            ];
        }

        $requestedScope = (string) $request->query('scope', '');
        $requestedSite = $request->query('site_id');
        $requestedSiteId = is_numeric($requestedSite) ? (int) $requestedSite : null;
        $requestedAll = $requestedSite === 'all';

        $isSiteManagerOnly = $user->isSiteManager() && !$user->isEnterpriseAdmin();

        if ($isSiteManagerOnly) {
            $siteId = (int) ($user->site_id ?? 0);
            if (!$siteId) {
                return [
                    'error' => response()->json([
                        'success' => false,
                        'message' => 'Site manager has no assigned site',
                    ], 403),
                ];
            }

            return [
                'enterprise_id' => $enterpriseId,
                'site_ids' => collect([$siteId]),
                'scope' => 'site',
                'site_id' => $siteId,
            ];
        }

        $scope = in_array($requestedScope, ['site', 'enterprise'], true) ? $requestedScope : 'enterprise';
        if ($requestedAll) {
            $scope = 'enterprise';
        }
        if ($requestedSiteId && $scope !== 'site') {
            $scope = 'site';
        }

        if ($scope === 'site') {
            if (!$requestedSiteId) {
                return [
                    'error' => response()->json([
                        'success' => false,
                        'message' => 'site_id is required when scope=site',
                    ], 422),
                ];
            }

            $site = Site::query()
                ->where('id', $requestedSiteId)
                ->where('enterprise_id', $enterpriseId)
                ->first();

            if (!$site) {
                return [
                    'error' => response()->json([
                        'success' => false,
                        'message' => 'Site not found or access denied',
                    ], 403),
                ];
            }

            return [
                'enterprise_id' => $enterpriseId,
                'site_ids' => collect([$requestedSiteId]),
                'scope' => 'site',
                'site_id' => $requestedSiteId,
            ];
        }

        /** @var Collection<int, int> $siteIds */
        $siteIds = Site::query()
            ->where('enterprise_id', $enterpriseId)
            ->pluck('id');

        return [
            'enterprise_id' => $enterpriseId,
            'site_ids' => $siteIds,
            'scope' => 'enterprise',
            'site_id' => null,
        ];
    }

    public function index()
    {
        $dashboards = Dashboard::with('process')->paginate(20);
        return DashboardResource::collection($dashboards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'process_id' => 'required|exists:processes,id',
            'year' => 'required|integer|min:2020|max:2100',
            'period' => 'nullable|string|max:255',
            'indicators' => 'nullable|array',
            'actions' => 'nullable|array',
        ]);

        $dashboard = Dashboard::create($validated);
        return new DashboardResource($dashboard->load('process'));
    }

    public function show(Dashboard $dashboard)
    {
        return new DashboardResource($dashboard->load('process'));
    }

    public function update(Request $request, Dashboard $dashboard)
    {
        $validated = $request->validate([
            'process_id' => 'sometimes|exists:processes,id',
            'year' => 'sometimes|integer|min:2020|max:2100',
            'period' => 'nullable|string|max:255',
            'indicators' => 'nullable|array',
            'actions' => 'nullable|array',
        ]);

        $dashboard->update($validated);
        return new DashboardResource($dashboard->load('process'));
    }

    public function destroy(Dashboard $dashboard)
    {
        $dashboard->delete();
        return response()->json(null, 204);
    }

    /**
     * Get dashboard statistics filtered by user's enterprise
     */
    public function getStats(Request $request)
    {
        try {
            $scope = $this->resolveDashboardScope($request);
            if (isset($scope['error'])) {
                return $scope['error'];
            }
            $enterpriseId = $scope['enterprise_id'];
            $siteIds = $scope['site_ids'];
            $isEnterpriseScope = $scope['scope'] === 'enterprise';
            $siteCount = $isEnterpriseScope
                ? Site::where('enterprise_id', $enterpriseId)->count()
                : $siteIds->count();
            $userCount = $isEnterpriseScope
                ? User::where('enterprise_id', $enterpriseId)->count()
                : User::whereIn('site_id', $siteIds)->count();

            // Calculate stats
            $stats = [
                'total_sites' => $siteCount,
                'total_users' => $userCount,
                'total_processes' => Process::whereIn('site_id', $siteIds)->count(),
                'total_risks' => Risk::whereIn('site_id', $siteIds)->count(),
                'total_actions' => Action::whereIn('site_id', $siteIds)->count(),
                'total_audits' => Audit::whereIn('site_id', $siteIds)->count(),
                'total_non_conformities' => NonConformity::whereIn('site_id', $siteIds)->count(),
                
                // Active/Overdue counts
                'active_non_conformities' => NonConformity::whereIn('site_id', $siteIds)
                    ->where('status', 'open')
                    ->count(),
                'overdue_actions' => Action::whereIn('site_id', $siteIds)
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', now())
                    ->count(),
                'upcoming_audits' => Audit::whereIn('site_id', $siteIds)
                    ->where('status', 'planned')
                    ->where('planned_date', '>', now())
                    ->where('planned_date', '<=', now()->addDays(30))
                    ->count(),
            ];

            // Charts data
            $charts = [
                'activity' => $this->getActivityChartData($siteIds),
                'distribution' => $this->getDistributionChartData($siteIds),
                'performance' => $this->getPerformanceChartData($siteIds),
            ];

            // Leadership stats
            $leadership = [
                'policy_status' => 'not_created',
                'policy_last_update' => null,
                'total_employees' => $userCount,
                'employees_with_job_description' => 0,
                'organization_chart_updated' => false,
            ];

            // Recent activities - last 10 actions or audits
            $recentActivities = collect();
            
            $recentActions = Action::whereIn('site_id', $siteIds)
                ->select('id', 'title as name', 'created_at', DB::raw("'action' as type"))
                ->latest()
                ->limit(5)
                ->get();
            
            $recentAudits = Audit::whereIn('site_id', $siteIds)
                ->select('id', 'title as name', 'created_at', DB::raw("'audit' as type"))
                ->latest()
                ->limit(5)
                ->get();
            
            $recentActivities = $recentActions->concat($recentAudits)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'charts' => $charts,
                    'leadership' => $leadership,
                    'recent_activities' => $recentActivities,
                    'scope' => [
                        'applied_scope' => $scope['scope'],
                        'applied_site_id' => $scope['site_id'],
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get KPIs data
     */
    public function getKpis(Request $request)
    {
        try {
            $scope = $this->resolveDashboardScope($request);
            if (isset($scope['error'])) {
                return $scope['error'];
            }
            $siteIds = $scope['site_ids'];

            $kpis = [
                'conformity_rate' => $this->calculateConformityRate($siteIds),
                'action_completion_rate' => $this->calculateActionCompletionRate($siteIds),
                'audit_compliance_rate' => $this->calculateAuditComplianceRate($siteIds),
                'risk_coverage' => $this->calculateRiskCoverage($siteIds),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'scope' => [
                        'applied_scope' => $scope['scope'],
                        'applied_site_id' => $scope['site_id'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching KPIs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard collaborateur global:
     * - ne remonte que les actions dont l'utilisateur courant est responsable
     * - scope site/enterprise appliqué comme le dashboard principal
     */
    public function getCollaboratorActions(Request $request)
    {
        try {
            $scope = $this->resolveDashboardScope($request);
            if (isset($scope['error'])) {
                return $scope['error'];
            }

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));
            $includeClosed = (bool) $request->boolean('include_closed', false);

            $baseQuery = Action::query()
                ->whereIn('site_id', $scope['site_ids'])
                ->where('responsible_id', (int) $user->id);

            if ($request->filled('process_id')) {
                $baseQuery->where('process_id', (int) $request->integer('process_id'));
            }

            if ($request->filled('site_id') && is_numeric($request->site_id)) {
                $baseQuery->where('site_id', (int) $request->site_id);
            }

            if ($request->filled('status')) {
                $baseQuery->where('status', (string) $request->string('status'));
            }

            $closedStatuses = ['completed', 'verified', 'closed', 'cancelled'];

            $listQuery = (clone $baseQuery)->with(['process:id,title,code', 'site:id,name']);
            if (!$includeClosed) {
                $listQuery->whereNotIn('status', $closedStatuses);
            }

            $actions = $listQuery
                ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
                ->orderBy('deadline')
                ->orderByDesc('updated_at')
                ->paginate($perPage);

            $openQuery = (clone $baseQuery)->whereNotIn('status', $closedStatuses);
            $today = now()->startOfDay();

            $stats = [
                'total_assigned' => (clone $baseQuery)->count(),
                'open_count' => (clone $openQuery)->count(),
                'in_progress_count' => (clone $openQuery)->where('status', 'in_progress')->count(),
                'overdue_count' => (clone $openQuery)->whereDate('deadline', '<', $today)->count(),
                'due_soon_count' => (clone $openQuery)
                    ->whereDate('deadline', '>=', $today)
                    ->whereDate('deadline', '<=', now()->addDays(7)->endOfDay())
                    ->count(),
                'completed_count' => (clone $baseQuery)->whereIn('status', ['completed', 'verified', 'closed'])->count(),
            ];

            $byProcessRows = (clone $baseQuery)
                ->selectRaw('process_id')
                ->selectRaw('COUNT(id) as total_count')
                ->selectRaw(
                    "SUM(CASE WHEN status NOT IN ('completed','verified','closed','cancelled') THEN 1 ELSE 0 END) as open_count"
                )
                ->selectRaw(
                    "SUM(CASE WHEN status NOT IN ('completed','verified','closed','cancelled') AND deadline < ? THEN 1 ELSE 0 END) as overdue_count",
                    [$today->toDateString()]
                )
                ->groupBy('process_id')
                ->orderByDesc('open_count')
                ->orderByDesc('total_count')
                ->limit(5)
                ->get();

            $processIds = $byProcessRows
                ->pluck('process_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            $processesById = Process::query()
                ->whereIn('id', $processIds)
                ->get(['id', 'title', 'code'])
                ->keyBy('id');

            $byProcess = $byProcessRows->map(function ($row) use ($processesById) {
                $processId = $row->process_id ? (int) $row->process_id : null;
                $process = $processId ? $processesById->get($processId) : null;
                $label = trim((string) ($process?->title ?? $process?->code ?? ''));

                return [
                    'process_id' => $processId,
                    'process_label' => $label !== '' ? $label : 'Sans processus',
                    'process_code' => $process?->code ? (string) $process->code : null,
                    'total_count' => (int) $row->total_count,
                    'open_count' => (int) $row->open_count,
                    'overdue_count' => (int) $row->overdue_count,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'by_process' => $byProcess,
                    'actions' => $actions->items(),
                    'pagination' => [
                        'current_page' => $actions->currentPage(),
                        'last_page' => $actions->lastPage(),
                        'per_page' => $actions->perPage(),
                        'total' => $actions->total(),
                    ],
                    'scope' => [
                        'applied_scope' => $scope['scope'],
                        'applied_site_id' => $scope['site_id'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard collaborateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chart data by type
     */
    public function getChartData(Request $request, $chartType)
    {
        try {
            $scope = $this->resolveDashboardScope($request);
            if (isset($scope['error'])) {
                return $scope['error'];
            }
            $siteIds = $scope['site_ids'];

            $chartData = match($chartType) {
                'non-conformities' => $this->getNonConformitiesChart($siteIds),
                'actions' => $this->getActionsChart($siteIds),
                'audits' => $this->getAuditsChart($siteIds),
                'risks' => $this->getRisksChart($siteIds),
                default => null
            };

            if ($chartData === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chart type'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chart' => $chartData,
                    'scope' => [
                        'applied_scope' => $scope['scope'],
                        'applied_site_id' => $scope['site_id'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper methods for KPIs
    private function calculateConformityRate($siteIds)
    {
        $total = NonConformity::whereIn('site_id', $siteIds)->count();
        if ($total === 0) return 100;
        
        $resolved = NonConformity::whereIn('site_id', $siteIds)
            ->where('status', 'closed')
            ->count();
        
        return round(($resolved / $total) * 100, 2);
    }

    private function calculateActionCompletionRate($siteIds)
    {
        $total = Action::whereIn('site_id', $siteIds)->count();
        if ($total === 0) return 0;
        
        $completed = Action::whereIn('site_id', $siteIds)
            ->where('status', 'completed')
            ->count();
        
        return round(($completed / $total) * 100, 2);
    }

    private function calculateAuditComplianceRate($siteIds)
    {
        $total = Audit::whereIn('site_id', $siteIds)->count();
        if ($total === 0) return 0;
        
        $compliant = Audit::whereIn('site_id', $siteIds)
            ->where('status', 'completed')
            ->count();
        
        return round(($compliant / $total) * 100, 2);
    }

    private function calculateRiskCoverage($siteIds)
    {
        $total = Risk::whereIn('site_id', $siteIds)->count();
        if ($total === 0) return 0;
        
        $managed = Risk::whereIn('site_id', $siteIds)
            ->whereNotNull('mitigation_plan')
            ->count();
        
        return round(($managed / $total) * 100, 2);
    }

    // Helper methods for charts
    private function getNonConformitiesChart($siteIds)
    {
        $data = NonConformity::whereIn('site_id', $siteIds)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date'),
            'values' => $data->pluck('count')
        ];
    }

    private function getActionsChart($siteIds)
    {
        $data = Action::whereIn('site_id', $siteIds)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status'),
            'values' => $data->pluck('count')
        ];
    }

    private function getAuditsChart($siteIds)
    {
        $data = Audit::whereIn('site_id', $siteIds)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $data->pluck('status'),
            'values' => $data->pluck('count')
        ];
    }

    private function getRisksChart($siteIds)
    {
        $data = Risk::whereIn('site_id', $siteIds)
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->get();

        return [
            'labels' => $data->pluck('severity'),
            'values' => $data->pluck('count')
        ];
    }

    // Helper methods for dynamic charts
    private function getActivityChartData($siteIds)
    {
        $weeks = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            
            $weeks[] = [
                'label' => 'Sem ' . (4 - $i),
                'documents' => 0, // TODO: Add Document model
                'actions' => Action::whereIn('site_id', $siteIds)
                    ->whereBetween('created_at', [$start, $end])->count(),
            ];
        }
        return $weeks;
    }

    private function getDistributionChartData($siteIds)
    {
        return [
            'documents' => 0, // TODO: Add Document model
            'nc' => NonConformity::whereIn('site_id', $siteIds)->count(),
            'audits' => Audit::whereIn('site_id', $siteIds)->count(),
            'actions' => Action::whereIn('site_id', $siteIds)->count(),
        ];
    }

    private function getPerformanceChartData($siteIds)
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            
            $totalActions = Action::whereIn('site_id', $siteIds)
                ->whereBetween('created_at', [$start, $end])->count();
            $completedActions = Action::whereIn('site_id', $siteIds)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', 'completed')->count();
            
            $months[] = [
                'label' => $start->format('M'),
                'objectives' => 0, // TODO: Add Objective model
                'actions' => $totalActions > 0 ? round(($completedActions / $totalActions) * 100) : 0,
            ];
        }
        return $months;
    }

    /**
     * Get user dashboard layout
     */
    public function getLayout(Request $request)
    {
        $layout = UserDashboardLayout::where('user_id', $request->user()->id)->first();
        
        if (!$layout) {
            return response()->json([
                'success' => true,
                'data' => $this->getDefaultLayout()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $layout->layout
        ]);
    }

    /**
     * Save user dashboard layout
     */
    public function saveLayout(Request $request)
    {
        $validated = $request->validate([
            'layout' => 'required|array',
        ]);

        UserDashboardLayout::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['layout' => $validated['layout']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Layout saved successfully'
        ]);
    }

    /**
     * Get default dashboard layout
     */
    private function getDefaultLayout()
    {
        return [
            'stats' => [
                ['id' => 'stat-0', 'position' => 0, 'visible' => true],
                ['id' => 'stat-1', 'position' => 1, 'visible' => true],
                ['id' => 'stat-2', 'position' => 2, 'visible' => true],
                ['id' => 'stat-3', 'position' => 3, 'visible' => true],
                ['id' => 'stat-4', 'position' => 4, 'visible' => true],
                ['id' => 'stat-5', 'position' => 5, 'visible' => true],
            ],
            'widgets' => [
                ['id' => 'tasks', 'position' => 0, 'visible' => true],
                ['id' => 'quick-actions', 'position' => 1, 'visible' => true],
                ['id' => 'leadership', 'position' => 2, 'visible' => true],
            ],
            'charts' => [
                ['id' => 'chart-activity', 'position' => 0, 'visible' => true],
                ['id' => 'chart-distribution', 'position' => 1, 'visible' => true],
            ],
        ];
    }
}
