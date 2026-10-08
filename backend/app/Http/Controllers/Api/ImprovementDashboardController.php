<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImprovementDashboardService;
use App\Policies\ImprovementDashboardPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImprovementDashboardController extends Controller
{
    public function __construct(protected ImprovementDashboardService $service)
    {
    }

    public function global(Request $request): JsonResponse
    {
        $this->authorize('viewGlobal', ImprovementDashboardPolicy::class);
        
        $filters = $request->only(['site_id', 'axes', 'date_from', 'date_to']);
        $dashboard = $this->service->getGlobalDashboard($filters);

        return response()->json($dashboard);
    }

    public function trends(Request $request): JsonResponse
    {
        $this->authorize('viewTrends', ImprovementDashboardPolicy::class);
        
        $filters = $request->only(['site_id', 'axes', 'months']);
        $trends = $this->service->getTrends($filters);

        return response()->json($trends);
    }

    public function axesComparison(Request $request): JsonResponse
    {
        $this->authorize('viewAxesComparison', ImprovementDashboardPolicy::class);
        
        $filters = $request->only(['site_id', 'date_from', 'date_to']);
        $comparison = $this->service->compareByAxes($filters);

        return response()->json($comparison);
    }

    public function alerts(Request $request): JsonResponse
    {
        $this->authorize('viewAlerts', ImprovementDashboardPolicy::class);
        
        $filters = $request->only(['site_id', 'axes']);
        $alerts = $this->service->getAlerts($filters);

        return response()->json($alerts);
    }

    public function exportPdf(Request $request): JsonResponse
    {
        $this->authorize('exportPdf', ImprovementDashboardPolicy::class);
        
        $filters = $request->only(['site_id', 'axes', 'date_from', 'date_to']);
        $pdf = $this->service->exportToPdf($filters);

        return response()->json([
            'url' => $pdf,
            'message' => 'Rapport généré avec succès',
        ]);
    }
}
