<?php

namespace App\Modules\Leadership\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseSubscription;
use App\Models\Norm;
use App\Models\User;
use App\Services\Context\SiteContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NormController extends Controller
{
    /**
     * Get all accessible norms for the current user.
     * During trial period (90 days): all norms.
     * After trial: only norms from active subscriptions.
     */
    public function index(Request $request, SiteContextService $siteContextService): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non autorisé'
            ], 403);
        }

        $site = $siteContextService->resolveForRequest($request);
        $siteId = $site?->id;
        
        $activeTrialSubscription = $this->resolveActiveTrialSubscription((int) $user->enterprise_id, $siteId);
        $isInTrialPeriod = (bool) $activeTrialSubscription;
        $query = $this->accessibleNormsQuery($user, $siteId);
        
        // Filter by domain if provided
        if ($request->has('domain')) {
            $query->where('domain', $request->domain);
        }
        
        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }
        
        $norms = $query->orderBy('code')->get();

        Log::info('Bibliotheque normes consultee', [
            'user_id' => $user->id,
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $siteId,
            'result' => 'allowed',
            'norms_count' => $norms->count(),
        ]);
        
        return response()->json([
            'success' => true,
            'trial_period' => $isInTrialPeriod,
            'trial_days_remaining' => $isInTrialPeriod
                ? now()->diffInDays($activeTrialSubscription->trial_ends_at, false)
                : 0,
            'data' => $norms->map(function($norm) {
                return [
                    'id' => $norm->id,
                    'code' => $norm->code,
                    'name' => $norm->name,
                    'title' => $norm->name, // Alias for compatibility
                    'domain' => $norm->domain,
                    'status' => $norm->status,
                    'has_pdf_document' => (bool) $norm->has_pdf_document,
                    'pdf_file_url' => $norm->pdf_file_url,
                    'pdf_original_name' => $norm->pdf_original_name,
                ];
            })
        ]);
    }

    /**
     * Get single norm with details
     */
    public function show(Request $request, SiteContextService $siteContextService, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non autorisé',
            ], 403);
        }

        $site = $siteContextService->resolveForRequest($request);
        $siteId = $site?->id;

        $norm = $this->accessibleNormsQuery($user, $siteId)
            ->with([
                'currentVersion.sections' => function ($query) {
                    $query
                        ->whereNull('parent_id')
                        ->orderBy('order_index')
                        ->with('children.children.children.children.children.children');
                },
            ])
            ->find($id);

        if (!$norm) {
            return $this->normNotAccessibleResponse(
                $request,
                (int) $id,
                $siteId,
                'show',
            );
        }

        Log::info('Norme consultee', [
            'user_id' => $user->id,
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $siteId,
            'norm_id' => $norm->id,
            'endpoint' => 'show',
            'result' => 'allowed',
        ]);
        
        return response()->json([
            'data' => $norm
        ]);
    }

    /**
     * Get chapters/subchapters for a norm current version.
     * Used by application scope forms.
     */
    public function chapters(Request $request, SiteContextService $siteContextService, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non autorisé',
            ], 403);
        }

        $site = $siteContextService->resolveForRequest($request);
        $siteId = $site?->id;

        $norm = $this->accessibleNormsQuery($user, $siteId)
            ->with(['currentVersion.sections'])
            ->find($id);

        if (!$norm) {
            return $this->normNotAccessibleResponse(
                $request,
                (int) $id,
                $siteId,
                'chapters',
            );
        }

        Log::info('Norme consultee', [
            'user_id' => $user->id,
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $siteId,
            'norm_id' => $norm->id,
            'endpoint' => 'chapters',
            'result' => 'allowed',
        ]);

        $version = $norm->currentVersion;

        if (!$version) {
            return response()->json(['data' => []]);
        }

        /** @var Collection<int, \App\Models\NormSection> $sections */
        $sections = $version->sections
            ->sortBy([
                ['order_index', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $chapters = $sections
            ->filter(function ($section) {
                return $section->parent_id === null
                    || $section->level === 1
                    || $section->type === 'chapter';
            })
            ->values()
            ->map(function ($chapter) use ($sections) {
                $subchapters = $sections
                    ->filter(function ($section) use ($chapter) {
                        if ((int) $section->parent_id === (int) $chapter->id) {
                            return true;
                        }

                        if (
                            $section->parent_id === null
                            || empty($chapter->number)
                            || empty($section->number)
                        ) {
                            return false;
                        }

                        return str_starts_with((string) $section->number, (string) $chapter->number . '.');
                    })
                    ->filter(function ($section) use ($chapter) {
                        return (int) $section->id !== (int) $chapter->id;
                    })
                    ->filter(function ($section) {
                        return $section->type === 'subchapter'
                            || (int) $section->level === 2
                            || str_contains((string) $section->number, '.');
                    })
                    ->values()
                    ->map(function ($subchapter) {
                        return [
                            'id' => $subchapter->id,
                            'number' => $subchapter->number,
                            'code' => $subchapter->number,
                            'title' => $subchapter->title,
                            'name' => $subchapter->title,
                        ];
                    });

                return [
                    'id' => $chapter->id,
                    'number' => $chapter->number,
                    'code' => $chapter->number,
                    'title' => $chapter->title,
                    'name' => $chapter->title,
                    'subchapters' => $subchapters->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $chapters,
        ]);
    }

    private function resolveActiveTrialSubscription(
        int $enterpriseId,
        ?int $siteId = null
    ): ?EnterpriseSubscription
    {
        return EnterpriseSubscription::query()
            ->where('is_trial', true)
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where(function ($query) use ($enterpriseId, $siteId) {
                if ($siteId) {
                    $query->where('site_id', $siteId);
                    return;
                }

                $query->whereHas('site', function ($siteQuery) use ($enterpriseId) {
                    $siteQuery->where('enterprise_id', $enterpriseId);
                });
            })
            ->orderByDesc('trial_ends_at')
            ->first();
    }

    private function accessibleNormsQuery(User $user, ?int $siteId = null): Builder
    {
        // Business rule: imported (draft) norms are visible immediately in company library.
        // Archived norms remain hidden.
        $query = Norm::query()->where('status', '!=', 'archived');

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $activeTrialSubscription = $this->resolveActiveTrialSubscription((int) $user->enterprise_id, $siteId);
        if ($activeTrialSubscription) {
            return $query;
        }

        return $query->whereHas('offers', function ($offerQuery) use ($user, $siteId) {
            $offerQuery->whereHas('subscriptions', function ($subscriptionQuery) use ($user, $siteId) {
                $subscriptionQuery
                    ->where(function ($scopeQuery) use ($user, $siteId) {
                        if ($siteId) {
                            $scopeQuery->where('site_id', $siteId);
                            return;
                        }

                        $scopeQuery->whereHas('site', function ($siteQuery) use ($user) {
                            $siteQuery->where('enterprise_id', $user->enterprise_id);
                        });
                    })
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where(function ($activeQuery) {
                        $activeQuery
                            ->where(function ($paidQuery) {
                                $paidQuery
                                    ->where(function ($isTrialQuery) {
                                        $isTrialQuery
                                            ->whereNull('is_trial')
                                            ->orWhere('is_trial', false);
                                    })
                                    ->whereNotNull('expiration_date')
                                    ->where('expiration_date', '>', now());
                            })
                            ->orWhere(function ($trialQuery) {
                                $trialQuery
                                    ->where('is_trial', true)
                                    ->whereNotNull('trial_ends_at')
                                    ->where('trial_ends_at', '>', now());
                            });
                    });
            });
        });
    }

    private function normNotAccessibleResponse(
        Request $request,
        int $normId,
        ?int $siteId,
        string $endpoint
    ): JsonResponse {
        $correlationId = (string) Str::uuid();

        Log::warning('Norme non accessible', [
            'correlation_id' => $correlationId,
            'endpoint' => $endpoint,
            'user_id' => $request->user()?->id,
            'enterprise_id' => $request->user()?->enterprise_id,
            'site_id' => $siteId,
            'norm_id' => $normId,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Norme non accessible',
            'correlation_id' => $correlationId,
        ], 404);
    }
}
