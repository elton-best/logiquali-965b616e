<?php

namespace App\Services;

use App\Models\NonConformity;
use App\Models\Audit;
use App\Models\Action;
use App\Models\Objective;
use App\Models\Risk;
use App\Models\Reclamation;
use App\Models\Indicateur;
use App\Models\Axe;
use Illuminate\Support\Facades\DB;

class ImprovementDashboardService
{
    public function getGlobalDashboard(array $filters = []): array
    {
        $siteId = $filters['site_id'] ?? null;
        $axes = $filters['axes'] ?? null;
        $dateFrom = $filters['date_from'] ?? now()->startOfYear();
        $dateTo = $filters['date_to'] ?? now();

        return [
            'summary' => $this->getSummary($siteId, $axes),
            'non_conformities' => $this->getNonConformitiesStats($siteId, $axes, $dateFrom, $dateTo),
            'audits' => $this->getAuditsStats($siteId, $axes, $dateFrom, $dateTo),
            'actions' => $this->getActionsStats($siteId, $axes),
            'objectives' => $this->getObjectivesStats($siteId, $axes),
            'risks' => $this->getRisksStats($siteId, $axes),
            'reclamations' => $this->getReclamationsStats($siteId, $axes, $dateFrom, $dateTo),
            'kpi' => $this->getKPIStats($siteId, $axes),
            'trends' => $this->getTrends($siteId, $axes, $dateFrom, $dateTo),
        ];
    }

    protected function getSummary(?int $siteId, ?array $axes): array
    {
        $query = fn($model) => $this->applyFilters($model::query(), $siteId, $axes);

        return [
            'total_nc' => $query(NonConformity::class)->count(),
            'total_audits' => $query(Audit::class)->count(),
            'total_actions' => $query(Action::class)->count(),
            'total_objectives' => $query(Objective::class)->count(),
            'total_risks' => $query(Risk::class)->where('type', 'risk')->count(),
            'total_opportunities' => $query(Risk::class)->where('type', 'opportunity')->count(),
            'total_reclamations' => $query(Reclamation::class)->count(),
            'total_kpi' => $query(Indicateur::class)->where('status', 'active')->count(),
            'active_axes' => Axe::active()->pluck('code')->toArray(),
        ];
    }

    protected function getNonConformitiesStats(?int $siteId, ?array $axes, $dateFrom, $dateTo): array
    {
        $query = $this->applyFilters(NonConformity::query(), $siteId, $axes)
            ->whereBetween('detected_at', [$dateFrom, $dateTo]);

        $ncs = $query->with('workflowState')->get();

        return [
            'total' => $ncs->count(),
            'open' => $ncs->filter(fn($nc) => !$nc->isFinal())->count(),
            'closed' => $ncs->filter(fn($nc) => $nc->isInState('closed'))->count(),
            'by_severity' => $ncs->groupBy('severity')->map->count(),
            'by_source' => $ncs->groupBy('source')->map->count(),
            'by_priority' => $ncs->groupBy('priority')->map->count(),
            'overdue' => $ncs->filter->isOverdue()->count(),
            'verified' => $ncs->where('effectiveness_verified', true)->count(),
            'avg_resolution_days' => $this->avgResolutionDays($ncs),
        ];
    }

    protected function getAuditsStats(?int $siteId, ?array $axes, $dateFrom, $dateTo): array
    {
        $query = $this->applyFilters(Audit::query(), $siteId, $axes)
            ->whereBetween('planned_date', [$dateFrom, $dateTo]);

        $audits = $query->get();

        return [
            'total' => $audits->count(),
            'completed' => $audits->filter(fn($a) => $a->isInState('completed'))->count(),
            'in_progress' => $audits->filter(fn($a) => $a->isInState('in_progress'))->count(),
            'planned' => $audits->filter(fn($a) => $a->isInState('planned'))->count(),
            'by_type' => $audits->groupBy('type')->map->count(),
            'avg_conformity_rate' => round($audits->whereNotNull('conformity_rate')->avg('conformity_rate') ?? 0, 1),
            'total_findings' => $audits->sum(fn($a) => count($a->findings ?? [])),
        ];
    }

    protected function getActionsStats(?int $siteId, ?array $axes): array
    {
        $query = $this->applyFilters(Action::query(), $siteId, $axes);
        $actions = $query->with('workflowState')->get();

        return [
            'total' => $actions->count(),
            'completed' => $actions->filter(fn($a) => $a->isInState('completed'))->count(),
            'in_progress' => $actions->filter(fn($a) => $a->isInState('in_progress'))->count(),
            'delayed' => $actions->filter(fn($a) => $a->isInState('delayed'))->count(),
            'by_type' => $actions->groupBy('type')->map->count(),
            'by_priority' => $actions->groupBy('priority')->map->count(),
            'avg_progress' => round($actions->avg('progress') ?? 0, 1),
            'overdue' => $actions->filter->isOverdue()->count(),
        ];
    }

    protected function getObjectivesStats(?int $siteId, ?array $axes): array
    {
        $query = $this->applyFilters(Objective::query(), $siteId, $axes);
        $objectives = $query->get();

        return [
            'total' => $objectives->count(),
            'active' => $objectives->filter(fn($o) => $o->isInState('active'))->count(),
            'achieved' => $objectives->filter(fn($o) => $o->isInState('achieved'))->count(),
            'not_achieved' => $objectives->filter(fn($o) => $o->isInState('not_achieved'))->count(),
            'by_type' => $objectives->groupBy('type')->map->count(),
            'avg_progress' => round($objectives->avg('progress') ?? 0, 1),
            'overdue' => $objectives->filter->isOverdue()->count(),
        ];
    }

    protected function getRisksStats(?int $siteId, ?array $axes): array
    {
        $query = $this->applyFilters(Risk::query(), $siteId, $axes);
        $risks = $query->where('type', 'risk')->get();

        return [
            'total' => $risks->count(),
            'high_priority' => $risks->filter->isHighRisk()->count(),
            'medium_priority' => $risks->filter->isMediumRisk()->count(),
            'low_priority' => $risks->count() - $risks->filter->isHighRisk()->count() - $risks->filter->isMediumRisk()->count(),
            'by_category' => $risks->groupBy('category')->map->count(),
            'controlled' => $risks->filter(fn($r) => $r->isInState('controlled'))->count(),
            'needing_review' => $risks->filter->needsReview()->count(),
        ];
    }

    protected function getReclamationsStats(?int $siteId, ?array $axes, $dateFrom, $dateTo): array
    {
        $query = $this->applyFilters(Reclamation::query(), $siteId, $axes)
            ->whereBetween('received_date', [$dateFrom, $dateTo]);

        $reclamations = $query->get();

        return [
            'total' => $reclamations->count(),
            'closed' => $reclamations->filter(fn($r) => $r->isInState('closed'))->count(),
            'by_category' => $reclamations->groupBy('category')->map->count(),
            'by_severity' => $reclamations->groupBy('severity')->map->count(),
            'avg_satisfaction' => round($reclamations->whereNotNull('satisfaction_rating')->avg('satisfaction_rating') ?? 0, 1),
        ];
    }

    protected function getKPIStats(?int $siteId, ?array $axes): array
    {
        $query = $this->applyFilters(Indicateur::query(), $siteId, $axes)
            ->where('status', 'active');

        $kpis = $query->get();

        return [
            'total' => $kpis->count(),
            'with_alerts' => $kpis->filter->needsAlert()->count(),
            'above_threshold' => $kpis->filter->isAboveThreshold()->count(),
            'below_threshold' => $kpis->filter->isBelowThreshold()->count(),
        ];
    }

    protected function getTrends(?int $siteId, ?array $axes, $dateFrom, $dateTo): array
    {
        $months = [];
        $current = $dateFrom->copy()->startOfMonth();
        
        while ($current <= $dateTo) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $months[] = [
                'month' => $current->format('Y-m'),
                'nc_count' => $this->applyFilters(NonConformity::query(), $siteId, $axes)
                    ->whereBetween('detected_at', [$monthStart, $monthEnd])
                    ->count(),
                'audits_count' => $this->applyFilters(Audit::query(), $siteId, $axes)
                    ->whereBetween('planned_date', [$monthStart, $monthEnd])
                    ->count(),
                'reclamations_count' => $this->applyFilters(Reclamation::query(), $siteId, $axes)
                    ->whereBetween('received_date', [$monthStart, $monthEnd])
                    ->count(),
            ];

            $current->addMonth();
        }

        return $months;
    }

    protected function applyFilters($query, ?int $siteId, ?array $axes)
    {
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($axes && count($axes) > 0) {
            $query->withAnyAxe($axes);
        }

        return $query;
    }

    protected function avgResolutionDays($collection): float
    {
        $resolved = $collection->filter(fn($item) => $item->resolution_date && $item->detected_at);
        
        if ($resolved->isEmpty()) {
            return 0;
        }

        return round($resolved->map(fn($item) => $item->detected_at->diffInDays($item->resolution_date))->avg(), 1);
    }

    public function getAxesComparison(array $filters = []): array
    {
        $siteId = $filters['site_id'] ?? null;
        $activeAxes = Axe::active()->get();

        $comparison = [];

        foreach ($activeAxes as $axe) {
            $comparison[$axe->code] = [
                'name' => $axe->name,
                'color' => $axe->color,
                'nc_count' => $this->applyFilters(NonConformity::query(), $siteId, [$axe->code])->count(),
                'audits_count' => $this->applyFilters(Audit::query(), $siteId, [$axe->code])->count(),
                'objectives_count' => $this->applyFilters(Objective::query(), $siteId, [$axe->code])->count(),
                'risks_count' => $this->applyFilters(Risk::query(), $siteId, [$axe->code])->where('type', 'risk')->count(),
            ];
        }

        return $comparison;
    }
}
