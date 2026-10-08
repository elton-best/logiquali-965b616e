<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\CommunicationPlan;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.communication.read')->only(['index', 'show']);
        $this->middleware('permission:support.communication.create')->only(['store']);
        $this->middleware('permission:support.communication.update')->only(['update', 'sync']);
        $this->middleware('permission:support.communication.delete')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = CommunicationPlan::query()->orderByDesc('year')->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }

        $plans = $query->get()->map(fn (CommunicationPlan $plan) => $this->serializePlan($plan));

        return response()->json($plans);
    }

    public function show(Request $request, CommunicationPlan $communicationPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($communicationPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        return response()->json($this->serializePlan($communicationPlan));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'status' => 'nullable|in:draft,active,closed',
            'planned_budget' => 'nullable|numeric|min:0',
            'planned_actions' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        $user = $request->user();
        $enterpriseId = $user?->enterprise_id;

        if (!$enterpriseId) {
            return response()->json(['message' => 'Aucune entreprise associée à l\'utilisateur'], 422);
        }

        $siteId = $validated['site_id'] ?? $user?->site_id;
        if ($siteId) {
            $siteExists = Site::query()
                ->where('id', $siteId)
                ->where('enterprise_id', $enterpriseId)
                ->exists();
            if (!$siteExists) {
                return response()->json(['message' => 'Site invalide pour votre entreprise'], 422);
            }
        }

        $alreadyExists = CommunicationPlan::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('site_id', $siteId)
            ->where('year', $validated['year'])
            ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'Un plan de communication existe déjà pour cette période'], 422);
        }

        $plan = CommunicationPlan::create([
            'enterprise_id' => $enterpriseId,
            'site_id' => $siteId,
            'year' => $validated['year'],
            'status' => $validated['status'] ?? 'draft',
            'planned_budget' => $validated['planned_budget'] ?? null,
            'planned_actions' => $validated['planned_actions'] ?? null,
            'spent_amount' => 0,
        ]);

        return response()->json($this->serializePlan($plan->fresh()), 201);
    }

    public function update(Request $request, CommunicationPlan $communicationPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($communicationPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:draft,active,closed',
            'planned_budget' => 'nullable|numeric|min:0',
            'planned_actions' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if (array_key_exists('site_id', $validated) && $validated['site_id']) {
            $siteExists = Site::query()
                ->where('id', $validated['site_id'])
                ->where('enterprise_id', $request->user()?->enterprise_id)
                ->exists();
            if (!$siteExists) {
                return response()->json(['message' => 'Site invalide pour votre entreprise'], 422);
            }
        }

        $communicationPlan->update($validated);
        $this->syncPlanTotals($communicationPlan);

        return response()->json($this->serializePlan($communicationPlan->fresh()));
    }

    public function destroy(Request $request, CommunicationPlan $communicationPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($communicationPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $hasCommunications = $this->baseCommunicationQuery($communicationPlan)->exists();
        if ($hasCommunications) {
            return response()->json([
                'message' => 'Suppression impossible: des actions sont rattachées à ce plan'
            ], 422);
        }

        $communicationPlan->delete();

        return response()->json(null, 204);
    }

    public function sync(Request $request, CommunicationPlan $communicationPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($communicationPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $this->syncPlanTotals($communicationPlan);

        return response()->json($this->serializePlan($communicationPlan->fresh()));
    }

    private function syncPlanTotals(CommunicationPlan $plan): void
    {
        $communications = $this->baseCommunicationQuery($plan)->get(['status', 'cout']);
        $spent = $communications
            ->filter(fn (Communication $communication) => $communication->status === 'realisee')
            ->sum('cout');

        $plan->update(['spent_amount' => $spent ?: 0]);
    }

    private function serializePlan(CommunicationPlan $plan): array
    {
        $communications = $this->baseCommunicationQuery($plan)->get(['id', 'status', 'cout', 'date_debut', 'date_fin']);

        $realisees = $communications->filter(fn (Communication $c) => $c->status === 'realisee')->count();
        $annulees = $communications->filter(fn (Communication $c) => $c->status === 'annulee')->count();
        $enAttente = $communications->filter(fn (Communication $c) => $c->status === 'en_attente')->count();
        $planifiees = $communications->filter(fn (Communication $c) => $c->status === 'planifiee')->count();
        $replanifiees = $communications->filter(fn (Communication $c) => $c->status === 'replanifiee')->count();
        $budgetRealise = $communications
            ->filter(fn (Communication $c) => $c->status === 'realisee')
            ->sum('cout');
        $budgetEngage = $communications
            ->filter(fn (Communication $c) => $c->status !== 'annulee')
            ->sum('cout');

        $budgetTotal = $plan->planned_budget !== null
            ? (float) $plan->planned_budget
            : (float) $communications->sum('cout');
        $budgetRestant = max(0, $budgetTotal - $budgetEngage);

        return [
            'id' => $plan->id,
            'enterprise_id' => $plan->enterprise_id,
            'site_id' => $plan->site_id,
            'year' => (int) $plan->year,
            'status' => $plan->status,
            'planned_budget' => $plan->planned_budget !== null ? (float) $plan->planned_budget : null,
            'planned_actions' => $plan->planned_actions !== null ? (int) $plan->planned_actions : null,
            'spent_amount' => (float) $budgetRealise,
            'budget_engaged' => (float) $budgetEngage,
            'budget_realized' => (float) $budgetRealise,
            'budget_remaining' => (float) $budgetRestant,
            'stats' => [
                'total_actions' => $communications->count(),
                'planifiees' => $planifiees,
                'replanifiees' => $replanifiees,
                'en_attente' => $enAttente,
                'realisees' => $realisees,
                'annulees' => $annulees,
                'taux_realisation' => $communications->count() > 0
                    ? round(($realisees / $communications->count()) * 100, 1)
                    : 0,
            ],
            'created_at' => $plan->created_at?->toISOString(),
            'updated_at' => $plan->updated_at?->toISOString(),
        ];
    }

    private function baseCommunicationQuery(CommunicationPlan $plan)
    {
        return Communication::query()
            ->where('enterprise_id', $plan->enterprise_id)
            ->when($plan->site_id !== null, fn ($query) => $query->where('site_id', $plan->site_id))
            ->where(function ($query) use ($plan) {
                $query->whereYear('date_debut', $plan->year)
                    ->orWhere(function ($nested) use ($plan) {
                        $nested->whereNull('date_debut')->where('plan_year', $plan->year);
                    });
            });
    }

    private function ensurePlanOwnership(CommunicationPlan $plan, Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->enterprise_id) {
            return response()->json(['message' => 'Utilisateur non associé à une entreprise'], 422);
        }

        if ((int) $plan->enterprise_id !== (int) $user->enterprise_id) {
            return response()->json(['message' => 'Accès non autorisé à ce plan de communication'], 403);
        }

        return null;
    }
}
