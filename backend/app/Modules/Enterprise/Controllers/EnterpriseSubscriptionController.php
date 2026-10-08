<?php

namespace App\Modules\Enterprise\Controllers;

use App\Models\Context;
use App\Models\Enterprise;
use App\Models\Offer;
use App\Models\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnterpriseSubscriptionResource;
use App\Models\EnterpriseSubscription;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class EnterpriseSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = EnterpriseSubscription::with(['offer.norms', 'site'])
            ->orderByDesc('created_at');

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        $subscriptions = $query->paginate(20);
        return EnterpriseSubscriptionResource::collection($subscriptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'site_id' => 'required|exists:sites,id',
            'start_date' => 'required|date',
            'expiration_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $subscription = EnterpriseSubscription::create($validated);
        return new EnterpriseSubscriptionResource($subscription->load(['offer', 'site']));
    }

    public function show(EnterpriseSubscription $enterpriseSubscription)
    {
        return new EnterpriseSubscriptionResource($enterpriseSubscription->load(['offer', 'site']));
    }

    public function update(Request $request, EnterpriseSubscription $enterpriseSubscription)
    {
        if ($request->has('offer_id') && (int) $request->input('offer_id') !== (int) $enterpriseSubscription->offer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Le remplacement de norme/offre est interdit. Renouvelez l\'offre existante et ajoutez une nouvelle norme.',
            ], 409);
        }

        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'start_date' => 'sometimes|date',
            'expiration_date' => 'sometimes|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $enterpriseSubscription->update($validated);
        return new EnterpriseSubscriptionResource($enterpriseSubscription->load(['offer', 'site']));
    }

    public function destroy(EnterpriseSubscription $enterpriseSubscription)
    {
        $enterpriseSubscription->delete();
        return response()->json(null, 204);
    }

    /**
     * Vérifie le statut d'abonnement d'un site
     */
    public function checkSiteSubscription(Request $request, $siteId)
    {
        try {
            $site = \App\Models\Site::findOrFail($siteId);
            $status = $site->getSubscriptionStatus();

            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Récupère tous les abonnements d'un site
     */
    public function getSiteSubscriptions($siteId)
    {
        try {
            $subscriptions = EnterpriseSubscription::where('site_id', $siteId)
                ->with(['offer'])
                ->orderBy('created_at', 'desc')
                ->get();

            return EnterpriseSubscriptionResource::collection($subscriptions);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements'
            ], 500);
        }
    }

    /**
     * Renouvelle un abonnement
     */
    public function renew(Request $request, EnterpriseSubscription $enterpriseSubscription)
    {
        $validated = $request->validate([
            'duration_months' => 'required|integer|min:1|max:36',
        ]);

        try {
            $offer = $enterpriseSubscription->offer;
            $oldExpirationDate = \Carbon\Carbon::parse($enterpriseSubscription->expiration_date);
            
            // CORRECTION: Continuer à partir de l'expiration de l'ancien abonnement
            $newStartDate = $oldExpirationDate;
            
            // Calculer arriérés si renouvellement en retard
            if (now()->gt($oldExpirationDate)) {
                $daysLate = now()->diffInDays($oldExpirationDate);
                
                if ($daysLate > 15) {
                    $monthsUnpaid = ceil($daysLate / 30);
                    $monthlyPrice = $offer->price / $offer->duration_months;
                    
                    $enterpriseSubscription->update([
                        'months_unpaid' => $monthsUnpaid,
                        'arrears_amount' => $monthsUnpaid * $monthlyPrice,
                        'last_payment_date' => now(),
                    ]);
                    
                    \Log::info("Arriérés calculés pour abonnement {$enterpriseSubscription->ref}", [
                        'months_unpaid' => $monthsUnpaid,
                        'arrears_amount' => $monthsUnpaid * $monthlyPrice,
                    ]);
                }
            }
            
            $newExpirationDate = $newStartDate->copy()->addMonths($validated['duration_months']);

            $enterpriseSubscription->update([
                'start_date' => $newStartDate,
                'expiration_date' => $newExpirationDate,
                'is_active' => true,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelé avec succès',
                'subscription' => new EnterpriseSubscriptionResource($enterpriseSubscription->load(['offer', 'site'])),
                'continued_from' => $oldExpirationDate->format('Y-m-d'),
                'new_expiration' => $newExpirationDate->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur renouvellement abonnement', [
                'subscription_id' => $enterpriseSubscription->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renouvellement de l\'abonnement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère la souscription active de l'utilisateur
     */
    public function current(
        Request $request,
        SiteContextService $siteContextService
    )
    {
        $site = $siteContextService->resolveForRequest($request);
        $subscriptions = $site?->getActiveSubscriptions();

        if (!$subscriptions || $subscriptions->isEmpty()) {
            return response()->json(['message' => 'Aucune souscription active'], 404);
        }

        return response()->json([
            'subscriptions' => EnterpriseSubscriptionResource::collection($subscriptions),
            'has_active_subscription' => true,
        ]);
    }

    /**
     * Récupère les modules accessibles (union de toutes les subscriptions)
     */
    public function accessibleModules(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    )
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        return response()->json(['modules' => $catalog['modules']]);
    }

    /**
     * Créer ou renouveler une souscription
     */
    public function subscribe(Request $request, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'offer_id' => 'required_without:offer_ids|exists:offers,id',
            'offer_ids' => 'required_without:offer_id|array',
            'offer_ids.*' => 'exists:offers,id',
            'site_id' => 'required|exists:sites,id',
            'is_trial' => 'boolean',
        ]);

        $user = $request->user();
        $site = \App\Models\Site::findOrFail($validated['site_id']);

        // Security: non-super-admin users can only subscribe their own enterprise sites.
        if (
            $user
            && $user->user_type !== 'super_admin'
            && (int) $site->enterprise_id !== (int) $user->enterprise_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas souscrire pour un site d\'une autre entreprise.',
            ], 403);
        }
        
        if ($validated['is_trial'] ?? false) {
            // VALIDATION: Seul le siège social peut bénéficier du trial
            if (!$site->is_headquarter) {
                return response()->json([
                    'success' => false,
                    'message' => 'La période d\'essai est réservée au siège social uniquement',
                    'hint' => 'Veuillez sélectionner le siège social pour activer la période d\'essai',
                ], 422);
            }
            
            // Vérifier si l'entreprise a déjà utilisé le trial (pour n'importe quel site)
            $hasTrialHistory = EnterpriseSubscription::whereHas('site', function($query) use ($site) {
                    $query->where('enterprise_id', $site->enterprise_id);
                })
                ->where('is_trial', true)
                ->exists();
            
            if ($hasTrialHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre entreprise a déjà bénéficié d\'une période d\'essai'
                ], 422);
            }
        }
        
        // Support both offer_id and offer_ids
        $offerIds = $validated['offer_ids'] ?? [$validated['offer_id']];
        
        $subscriptions = [];
        
        $activeSubscriptions = $site->getActiveSubscriptions();
        
        foreach ($offerIds as $offerId) {
            $offer = \App\Models\Offer::findOrFail($offerId);
            $offerNormIds = $offer->norms->pluck('id')->all();
            
            // VALIDATION: Vérifier si une norme de cette offre est déjà active (Vérifie date expiration)
            $conflictingActive = $activeSubscriptions->filter(function ($sub) use ($offerNormIds) {
                return $sub->offer->norms->pluck('id')->intersect($offerNormIds)->isNotEmpty();
            });
            
            if ($conflictingActive->isNotEmpty()) {
                $duplicateNorms = $conflictingActive->flatMap(function ($sub) {
                    return $sub->offer->norms;
                })->pluck('name')->unique();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà un abonnement actif pour cette norme',
                    'duplicate_norms' => $duplicateNorms,
                    'hint' => 'Vous ne pouvez pas changer de norme, seulement en ajouter de nouvelles ou renouveler les existantes.',
                ], 422);
            }
            
            // Vérifier si une souscription active existe déjà pour cette offre exacte
            if ($activeSubscriptions->contains('offer_id', $offerId)) {
                continue; // Ignorer ce doublon
            }
            
            $offer = \App\Models\Offer::findOrFail($offerId);
            
            // Déterminer si c'est primary ou addon (basé sur les souscriptions réellement actives)
            $isPrimary = $activeSubscriptions->isEmpty() && empty($subscriptions);

            // Si trial, créer trial subscription
            if ($validated['is_trial'] ?? false) {
                $subscription = $subscriptionService->createTrialSubscription($site, $offer, $isPrimary);
            } else {
                // Créer subscription active directement (sans paiement requis)
                $subscription = EnterpriseSubscription::create([
                    'site_id' => $site->id,
                    'offer_id' => $offer->id,
                    'start_date' => now(),
                    'expiration_date' => now()->addMonths($offer->duration_months),
                    'is_active' => true,
                    'is_trial' => false,
                    'status' => 'active',
                    'payment_status' => 'completed',
                    'subscription_type' => $isPrimary ? 'primary' : 'addon',
                ]);
            }
            
            $subscriptions[] = $subscription;
        }

        // Marquer le domaine d'activité comme configuré après première souscription
        if (!$site->enterprise->domaine_activite_set) {
            $site->enterprise->update(['domaine_activite_set' => true]);
        }

        return response()->json([
            'message' => ($validated['is_trial'] ?? false)
                ? 'Souscription d\'essai créée avec succès'
                : 'Souscription créée et activée avec succès.',
            'requires_payment' => false,
            'subscriptions' => EnterpriseSubscriptionResource::collection(
                collect($subscriptions)->each(fn($sub) => $sub->load(['offer', 'site']))
            ),
        ], 201);
    }

    /**
     * Récupère les alertes non lues
     */
    public function alerts(Request $request)
    {
        $user = $request->user();
        $alerts = $user->unreadNotifications()->where('type', 'like', '%Trial%')->get();

        return response()->json(['alerts' => $alerts]);
    }
}
