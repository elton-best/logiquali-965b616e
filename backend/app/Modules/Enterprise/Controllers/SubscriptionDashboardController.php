<?php

namespace App\Modules\Enterprise\Controllers;

use App\Models\Dashboard;
use App\Models\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionDashboardController extends Controller
{
    /**
     * Dashboard complet des abonnements d'un site
     */
    public function index()
    {
        $user = Auth::user();
        $site = $user->site;

        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun site associé à l\'utilisateur',
            ], 404);
        }

        $allSubscriptions = $site->subscriptions()
            ->with(['offer.norms'])
            ->orderBy('expiration_date', 'asc')
            ->get();

        $activeSubscriptions = $allSubscriptions->filter(fn($sub) => $sub->isActive());
        $expiredSubscriptions = $allSubscriptions->filter(fn($sub) => !$sub->isActive());

        // Calculer statistiques globales
        $totalModules = 0;
        $totalSubModules = 0;
        $activeNorms = collect();

        foreach ($activeSubscriptions as $subscription) {
            $modules = $subscription->getAccessibleModules();
            $subModules = $subscription->getAccessibleSubModules();
            $totalModules += $modules->count();
            $totalSubModules += $subModules->count();
            $activeNorms = $activeNorms->merge($subscription->offer->norms);
        }

        $uniqueModules = $totalModules > 0 ? collect()->unique('id')->count() : 0;
        $uniqueNorms = $activeNorms->unique('id');

        // Timeline des expirations (6 prochains mois)
        $timeline = [];
        $now = Carbon::now();
        for ($i = 0; $i < 6; $i++) {
            $month = $now->copy()->addMonths($i);
            $expiringInMonth = $activeSubscriptions->filter(function ($sub) use ($month) {
                $expiration = Carbon::parse($sub->expiration_date);
                return $expiration->year === $month->year && $expiration->month === $month->month;
            });

            $timeline[] = [
                'month' => $month->format('M Y'),
                'count' => $expiringInMonth->count(),
                'subscriptions' => $expiringInMonth->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'ref' => $sub->ref,
                        'norms' => $sub->offer->norms->pluck('name'),
                        'expiration_date' => $sub->expiration_date,
                    ];
                }),
            ];
        }

        // Alertes urgentes
        $urgentAlerts = $activeSubscriptions->filter(function ($sub) {
            $daysRemaining = $sub->daysRemaining();
            return $daysRemaining <= 14;
        })->map(function ($sub) {
            return [
                'id' => $sub->id,
                'ref' => $sub->ref,
                'norms' => $sub->offer->norms->pluck('name'),
                'days_remaining' => $sub->daysRemaining(),
                'expiration_date' => $sub->expiration_date,
                'severity' => $sub->daysRemaining() <= 3 ? 'critical' : ($sub->daysRemaining() <= 7 ? 'high' : 'medium'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => [
                    'active_subscriptions_count' => $activeSubscriptions->count(),
                    'expired_subscriptions_count' => $expiredSubscriptions->count(),
                    'active_norms_count' => $uniqueNorms->count(),
                    'active_norms' => $uniqueNorms->pluck('name'),
                    'total_modules_accessible' => $totalModules,
                    'total_submodules_accessible' => $totalSubModules,
                ],
                'active_subscriptions' => $activeSubscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'ref' => $sub->ref,
                        'norms' => $sub->offer->norms->map(fn($n) => [
                            'id' => $n->id,
                            'name' => $n->name,
                            'code' => $n->code,
                        ]),
                        'start_date' => $sub->start_date,
                        'expiration_date' => $sub->expiration_date,
                        'days_remaining' => $sub->daysRemaining(),
                        'is_trial' => $sub->isInTrial(),
                        'is_expiring_soon' => $sub->daysRemaining() <= 14,
                        'status' => $sub->status,
                        'modules_count' => $sub->getAccessibleModules()->count(),
                    ];
                })->values(),
                'expired_subscriptions' => $expiredSubscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'ref' => $sub->ref,
                        'norms' => $sub->offer->norms->pluck('name'),
                        'expiration_date' => $sub->expiration_date,
                        'days_expired' => now()->diffInDays($sub->expiration_date),
                        'arrears_amount' => $sub->arrears_amount ?? 0,
                        'months_unpaid' => $sub->months_unpaid ?? 0,
                    ];
                })->values(),
                'timeline' => $timeline,
                'urgent_alerts' => $urgentAlerts,
            ],
        ]);
    }

    /**
     * Actions rapides disponibles
     */
    public function quickActions()
    {
        $user = Auth::user();
        $site = $user->site;

        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun site associé',
            ], 404);
        }

        $activeSubscriptions = $site->getActiveSubscriptions();
        $canAddTrial = !EnterpriseSubscription::whereHas('site', function ($query) use ($site) {
            $query->where('enterprise_id', $site->enterprise_id);
        })
            ->where('is_trial', true)
            ->exists();

        $availableActions = [
            [
                'id' => 'add_norm',
                'label' => 'Ajouter une norme',
                'icon' => 'mdi-plus-circle',
                'color' => 'primary',
                'enabled' => $activeSubscriptions->isNotEmpty(),
                'description' => 'Souscrire à une norme supplémentaire',
            ],
            [
                'id' => 'renew',
                'label' => 'Renouveler un abonnement',
                'icon' => 'mdi-refresh',
                'color' => 'success',
                'enabled' => $activeSubscriptions->isNotEmpty(),
                'description' => 'Prolonger un abonnement existant',
            ],
            [
                'id' => 'trial',
                'label' => 'Période d\'essai',
                'icon' => 'mdi-star',
                'color' => 'warning',
                'enabled' => $canAddTrial && $site->is_headquarter,
                'description' => 'Activer la période d\'essai gratuite',
            ],
        ];

        return response()->json([
            'success' => true,
            'actions' => $availableActions,
        ]);
    }
}
