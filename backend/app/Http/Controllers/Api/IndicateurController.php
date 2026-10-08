<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndicateurRequest;
use App\Http\Requests\UpdateIndicateurRequest;
use App\Http\Requests\AddIndicateurValueRequest;
use App\Models\Indicateur;
use App\Services\IndicateurService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class IndicateurController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected IndicateurService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Indicateur::class);

        $query = Indicateur::with(['objectives', 'responsible', 'axes']);

        if ($request->has('axes')) {
            $axes = is_array($request->axes) ? $request->axes : [$request->axes];
            $query->withAnyAxe($axes);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $indicateurs = $query->get();

        return response()->json($indicateurs);
    }

    public function store(StoreIndicateurRequest $request): JsonResponse
    {
        $this->authorize('create', Indicateur::class);
        
        $indicateur = $this->service->create($request->validated());

        return response()->json($indicateur, 201);
    }

    public function show(int $id): JsonResponse
    {
        $indicateur = Indicateur::with(['objectives', 'responsible', 'axes'])->findOrFail($id);
        $this->authorize('view', $indicateur);

        return response()->json($indicateur);
    }

    public function update(UpdateIndicateurRequest $request, int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $this->authorize('update', $indicateur);
        
        $indicateur->update($request->validated());

        return response()->json($indicateur);
    }

    public function destroy(int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $this->authorize('delete', $indicateur);
        
        $indicateur->delete();
        return response()->json(['message' => 'Indicateur supprimé'], 200);
    }

    public function addValue(AddIndicateurValueRequest $request, int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $validated = $request->validated();

        $indicateur = $this->service->addValue(
            $indicateur, 
            $validated['value'], 
            $validated['date'], 
            $validated['notes'] ?? null
        );

        return response()->json($indicateur);
    }

    public function getChartData(Request $request, int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $this->authorize('viewChartData', Indicateur::class);
        
        $filters = $request->only(['date_from', 'date_to', 'frequency']);
        $data = $this->service->getChartData($indicateur, $filters);

        return response()->json($data);
    }

    public function checkThresholds(int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $this->authorize('checkThresholds', Indicateur::class);
        
        $alerts = $this->service->checkThresholds($indicateur);

        return response()->json($alerts);
    }

    /**
     * Get KPI trend over time
     */
    public function trend(Request $request, int $id): JsonResponse
    {
        $indicateur = Indicateur::findOrFail($id);
        $this->authorize('view', $indicateur);
        
        $months = $request->integer('months', 12);
        
        $values = $indicateur->values()
            ->where('date', '>=', now()->subMonths($months))
            ->orderBy('date')
            ->get();
        
        if ($values->count() < 2) {
            return response()->json([
                'data' => [
                    'values' => $values,
                    'trend' => null,
                    'message' => 'Insufficient data for trend analysis (need at least 2 data points)',
                ],
            ]);
        }
        
        $firstValue = $values->first()->value;
        $lastValue = $values->last()->value;
        $targetValue = $indicateur->target_value;
        
        // Calculate percentage change
        $percentageChange = $firstValue != 0 
            ? (($lastValue - $firstValue) / $firstValue) * 100 
            : 0;
        
        // Determine if improving (depends on indicator type)
        $higherIsBetter = $indicateur->higher_is_better ?? true;
        $isImproving = $higherIsBetter
            ? $lastValue > $firstValue
            : $lastValue < $firstValue;
        
        // Target achievement
        $targetAchievement = $targetValue > 0 
            ? min(($lastValue / $targetValue) * 100, 100)
            : null;
        
        return response()->json([
            'data' => [
                'values' => $values->map(fn($v) => [
                    'period' => $v->date->format('Y-m'),
                    'value' => $v->value,
                    'target' => $targetValue,
                    'date' => $v->date,
                ]),
                'trend' => [
                    'direction' => $percentageChange > 0.5 ? 'up' : ($percentageChange < -0.5 ? 'down' : 'stable'),
                    'percentage' => round(abs($percentageChange), 2),
                    'is_improving' => $isImproving,
                    'first_value' => $firstValue,
                    'last_value' => $lastValue,
                ],
                'target_achievement' => round($targetAchievement ?? 0, 2),
                'current_status' => $this->calculateKPIStatus($lastValue, $indicateur),
            ],
        ]);
    }

    private function calculateKPIStatus(float $value, Indicateur $kpi): string
    {
        if ($kpi->target_value === null || $kpi->target_value == 0) {
            return 'unknown';
        }
        
        $achievement = ($value / $kpi->target_value) * 100;
        
        return match(true) {
            $achievement >= 100 => 'excellent',
            $achievement >= 90 => 'on_target',
            $achievement >= 75 => 'at_risk',
            default => 'critical',
        };
    }
}
