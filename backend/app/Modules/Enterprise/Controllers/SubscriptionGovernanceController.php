<?php

namespace App\Modules\Enterprise\Controllers;

use App\Models\Context;
use App\Models\Enterprise;
use App\Models\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnterpriseSubscriptionResource;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use App\Services\Context\SiteContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionGovernanceController extends Controller
{
    public function status(Request $request, SiteContextService $siteContextService)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
        ]);

        $site = $siteContextService->resolveForRequest($request, $validated['site_id'] ?? null);

        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site non autorisé ou introuvable',
            ], 403);
        }

        $coverage = $site->getNormCoverageStatus();
        $activeSubscriptions = $site->getActiveSubscriptions();
        $trialUsed = EnterpriseSubscription::whereHas('site', function ($query) use ($site) {
                $query->where('enterprise_id', $site->enterprise_id);
            })
            ->where('is_trial', true)
            ->exists();

        return response()->json([
            'success' => true,
            'site_id' => $site->id,
            'trialUsed' => $trialUsed,
            'hasActiveSubscription' => $coverage['can_access_dashboard'],
            'has_any_subscription' => $coverage['has_any_subscription'],
            'can_access_dashboard' => $coverage['can_access_dashboard'],
            'active_norms' => $coverage['active_norms'],
            'expired_norms' => $coverage['expired_norms'],
            'blocking_reasons' => $coverage['blocking_reasons'],
            'active_subscriptions_count' => $activeSubscriptions->count(),
            'active_subscriptions' => EnterpriseSubscriptionResource::collection($activeSubscriptions),
        ]);
    }

    public function addNorm(Request $request, SiteContextService $siteContextService)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
            'offer_id' => 'required|integer|exists:offers,id',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $site = $siteContextService->resolveForRequest($request, $validated['site_id'] ?? null);
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site non autorisé ou introuvable',
            ], 403);
        }

        $offer = Offer::with('norms')->findOrFail($validated['offer_id']);
        if ($offer->norms->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette offre ne contient aucune norme',
            ], 422);
        }

        $offerNormIds = $offer->norms->pluck('id')->all();

        // Interdire le "switch": impossible d'ajouter une offre qui chevauche une norme déjà active.
        $conflictingActive = $site->subscriptions()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('expiration_date', '>', now())
            ->whereHas('offer.norms', function ($query) use ($offerNormIds) {
                $query->whereIn('norms.id', $offerNormIds);
            })
            ->with('offer.norms')
            ->get();

        if ($conflictingActive->isNotEmpty()) {
            $normCodes = $conflictingActive
                ->flatMap(fn ($sub) => $sub->offer->norms)
                ->pluck('code')
                ->unique()
                ->values()
                ->all();

            return response()->json([
                'success' => false,
                'message' => 'Remplacement de norme interdit: renouvelez la norme existante et ajoutez une nouvelle norme différente.',
                'conflicting_norms' => $normCodes,
            ], 409);
        }

        $subscription = null;
        DB::transaction(function () use ($site, $offer, $validated, &$subscription) {
            $existingCount = $site->subscriptions()
                ->where('is_active', true)
                ->where('expiration_date', '>', now())
                ->count();

            $subscription = EnterpriseSubscription::create([
                'site_id' => $site->id,
                'offer_id' => $offer->id,
                'start_date' => now(),
                'expiration_date' => now()->copy()->addMonths($offer->duration_months),
                'is_active' => true,
                'is_trial' => false,
                'status' => 'active',
                'payment_status' => 'completed',
                'subscription_type' => $existingCount === 0 ? 'primary' : 'addon',
                'payment_method' => $validated['payment_method'] ?? null,
                'amount_paid' => $offer->price,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Norme ajoutée avec succès',
            'subscription' => new EnterpriseSubscriptionResource($subscription->load(['offer.norms', 'site'])),
        ], 201);
    }

    public function renewNorm(Request $request, SiteContextService $siteContextService)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
            'offer_id' => 'required|integer|exists:offers,id',
            'duration_months' => 'nullable|integer|min:1|max:36',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $site = $siteContextService->resolveForRequest($request, $validated['site_id'] ?? null);
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site non autorisé ou introuvable',
            ], 403);
        }

        $offer = Offer::with('norms')->findOrFail($validated['offer_id']);
        $durationMonths = (int) ($validated['duration_months'] ?? $offer->duration_months);

        $latest = $site->subscriptions()
            ->where('offer_id', $offer->id)
            ->orderByDesc('expiration_date')
            ->first();

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune souscription existante pour cette norme/offre. Utilisez add-norm.',
            ], 422);
        }

        $newStartDate = $latest->expiration_date && $latest->expiration_date->gt(now())
            ? $latest->expiration_date->copy()
            : now();
        $newExpirationDate = $newStartDate->copy()->addMonths($durationMonths);

        $overlap = $site->subscriptions()
            ->where('offer_id', $offer->id)
            ->where('is_active', true)
            ->where(function ($query) use ($newStartDate, $newExpirationDate) {
                $query->where('start_date', '<', $newExpirationDate)
                    ->where('expiration_date', '>', $newStartDate);
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Un renouvellement existe déjà pour cette période',
            ], 409);
        }

        $renewal = null;
        DB::transaction(function () use ($site, $offer, $durationMonths, $newStartDate, $newExpirationDate, $validated, &$renewal) {
            $renewal = EnterpriseSubscription::create([
                'site_id' => $site->id,
                'offer_id' => $offer->id,
                'start_date' => $newStartDate,
                'expiration_date' => $newExpirationDate,
                'is_active' => true,
                'is_trial' => false,
                'status' => 'active',
                'payment_status' => 'completed',
                'subscription_type' => 'addon',
                'payment_method' => $validated['payment_method'] ?? null,
                'amount_paid' => ($offer->price / max($offer->duration_months, 1)) * $durationMonths,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Renouvellement enregistré',
            'takes_effect_at' => $newStartDate->toISOString(),
            'new_expiration_date' => $newExpirationDate->toISOString(),
            'subscription' => new EnterpriseSubscriptionResource($renewal->load(['offer.norms', 'site'])),
        ], 201);
    }

}
