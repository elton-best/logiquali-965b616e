<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\EnterpriseDocument;
use App\Models\EnterpriseSubscription;
use App\Models\User;
use App\Notifications\Enterprise\EnterpriseApprovedNotification;
use App\Notifications\Enterprise\EnterpriseRejectedNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Utils\ExportHeaders;

class SuperAdminController extends Controller
{
    public function getDashboardStats()
    {
        try {
            $stats = Cache::remember('superadmin.dashboard.stats', 60, function () {
                $weekStarts = collect(range(0, 9))->map(function ($offset) {
                    return now()->startOfWeek()->subWeeks(9 - $offset);
                });

                $revenueSeries = $weekStarts->map(function ($start) {
                    $end = $start->copy()->endOfWeek();

                    return (float) (EnterpriseSubscription::join('offers', 'enterprise_subscriptions.offer_id', '=', 'offers.id')
                        ->whereBetween('enterprise_subscriptions.created_at', [$start, $end])
                        ->sum('offers.price') ?? 0);
                })->values();

                $lastWeekRevenue = $revenueSeries->last() ?? 0;
                $previousWeekRevenue = $revenueSeries->count() > 1 ? $revenueSeries[$revenueSeries->count() - 2] : 0;
                $revenueTrendPct = 0;
                if ($previousWeekRevenue > 0) {
                    $revenueTrendPct = (($lastWeekRevenue - $previousWeekRevenue) / $previousWeekRevenue) * 100;
                } elseif ($lastWeekRevenue > 0) {
                    $revenueTrendPct = 100;
                }

                return [
                    'kyc' => [
                        'pending' => Enterprise::where('approval_status', 'pending')->count(),
                        'approved' => Enterprise::where('approval_status', 'approved')->count(),
                        'rejected' => Enterprise::where('approval_status', 'rejected')->count(),
                        'total' => Enterprise::count(),
                    ],
                    'companies' => [
                        'total' => Enterprise::count(),
                        'active' => Enterprise::where('status', 'active')->count(),
                        'suspended' => Enterprise::where('status', 'suspended')->count(),
                    ],
                    'subscriptions' => [
                        'active' => EnterpriseSubscription::where('is_active', true)->count(),
                        'total' => EnterpriseSubscription::count(),
                    ],
                    'revenue' => [
                        'total' => EnterpriseSubscription::join('offers', 'enterprise_subscriptions.offer_id', '=', 'offers.id')
                            ->where('enterprise_subscriptions.is_active', true)
                            ->sum('offers.price') ?? 0,
                        'series' => $revenueSeries,
                        'trend_pct' => round($revenueTrendPct, 1),
                    ],
                    'weekly' => [
                        'new_registrations' => Enterprise::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                        'kyc_validations' => Enterprise::where('status', 'active')->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    ],
                ];
            });
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement du tableau de bord.',
                'SUPERADMIN_DASHBOARD_STATS_ERROR',
                500,
                ['scope' => 'dashboard_stats'],
                $e
            );
        }
    }

    public function getEnterprises(Request $request)
    {
        try {
            $query = Enterprise::query()->with(['users' => function ($q) {
                $q->where('user_type', 'company')->limit(1);
            }]);
            
            // Filter by operational status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Filter by approval status (KYC validation)
            if ($request->has('approval_status') && $request->approval_status !== 'all') {
                $query->where('approval_status', $request->approval_status);
            }
            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('registration_number', 'like', '%' . $request->search . '%');
                });
            }
            $perPage = $request->get('per_page', 15);
            if ($request->boolean('kyc_priority')) {
                $query->orderByRaw("CASE 
                    WHEN approval_status = 'pending' THEN 0
                    WHEN approval_status = 'approved' THEN 1
                    WHEN approval_status = 'rejected' THEN 2
                    ELSE 3
                END");
            }
            $enterprises = $query->orderBy('created_at', 'desc')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $enterprises->items(),
                'meta' => [
                    'current_page' => $enterprises->currentPage(),
                    'last_page' => $enterprises->lastPage(),
                    'per_page' => $enterprises->perPage(),
                    'total' => $enterprises->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des entreprises.',
                'SUPERADMIN_ENTERPRISES_FETCH_ERROR',
                500,
                ['scope' => 'enterprises_list'],
                $e
            );
        }
    }

    /**
     * Get a single enterprise by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEnterprise($id)
    {
        try {
            $enterprise = Enterprise::with(['users.roles', 'sites', 'documents'])->findOrFail($id);

            // Add signed URLs to documents
            $enterprise->documents->each(function ($document) {
                $document->download_url = $document->getSignedDownloadUrl();
                $document->preview_url = $document->getSignedPreviewUrl();
                $document->file_size = $document->file_size;
                $document->file_extension = $document->file_extension;
            });

            $adminUser = $enterprise->users
                ->first(fn ($user) => $user->hasRole('admin_entreprise'));
            if (!$adminUser) {
                $adminUser = $enterprise->users->first();
            }

            $enterprise->setAttribute('enterprise_admin', $adminUser ? [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'phone' => $adminUser->phone,
                'role_names' => method_exists($adminUser, 'getRoleNames')
                    ? $adminUser->getRoleNames()->values()->all()
                    : [],
            ] : null);

            return response()->json(['success' => true, 'data' => $enterprise]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                'Entreprise non trouvée.',
                'SUPERADMIN_ENTERPRISE_NOT_FOUND',
                404
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_FETCH_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    /**
     * Approve an enterprise (KYC) by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveEnterprise($id)
    {
        try {
            $enterprise = Enterprise::findOrFail($id);
            $enterprise->update([
                'status' => 'active',
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null
            ]);
            
            // Déclencher l'event pour notification
            event(new \App\Events\Enterprise\EnterpriseApproved($enterprise));
            
            return response()->json(['success' => true, 'message' => 'Entreprise approuvée', 'data' => $enterprise]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de l’approbation de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_APPROVE_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    /**
     * Reject an enterprise with a reason.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectEnterprise(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|min:10']);
        try {
            $enterprise = Enterprise::findOrFail($id);
            $enterprise->update([
                'status' => 'rejected',
                'approval_status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $request->reason
            ]);
            
            // Déclencher l'event pour notification
            event(new \App\Events\Enterprise\EnterpriseRejected($enterprise, $request->reason));
            
            return response()->json(['success' => true, 'message' => 'Entreprise rejetée', 'data' => $enterprise]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du rejet de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_REJECT_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    /**
     * Suspend an enterprise with a reason.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspendEnterprise(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|min:10']);
        try {
            $enterprise = Enterprise::findOrFail($id);
            $enterprise->update(['status' => 'suspended', 'suspension_reason' => $request->reason]);
            return response()->json(['success' => true, 'message' => 'Entreprise suspendue', 'data' => $enterprise]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suspension de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_SUSPEND_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    /**
     * Reactivate an enterprise by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reactivateEnterprise($id)
    {
        try {
            $enterprise = Enterprise::findOrFail($id);
            $enterprise->update(['status' => 'active', 'suspension_reason' => null]);
            return response()->json(['success' => true, 'message' => 'Entreprise réactivée', 'data' => $enterprise]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la réactivation de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_REACTIVATE_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    /**
     * Delete an enterprise by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEnterprise($id)
    {
        try {
            $enterprise = Enterprise::with('sites')->findOrFail($id);

            // Check if has active subscriptions via sites
            $activeSubscriptions = 0;
            foreach ($enterprise->sites as $site) {
                $activeSubscriptions += EnterpriseSubscription::where('site_id', $site->id)
                    ->where('is_active', true)
                    ->count();
            }

            if ($activeSubscriptions > 0) {
                return $this->errorResponse(
                    "Impossible de supprimer cette entreprise car elle a {$activeSubscriptions} abonnement(s) actif(s)",
                    'SUPERADMIN_ENTERPRISE_DELETE_BLOCKED_ACTIVE_SUBSCRIPTIONS',
                    422
                );
            }

            $enterprise->delete();
            return response()->json(['success' => true, 'message' => 'Entreprise supprimée']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression de l’entreprise.',
                'SUPERADMIN_ENTERPRISE_DELETE_ERROR',
                500,
                ['enterprise_id' => $id],
                $e
            );
        }
    }

    public function getNorms()
    {
        try {
            $norms = Norm::orderBy('name')->get();
            return response()->json(['success' => true, 'data' => $norms]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des normes.',
                'SUPERADMIN_NORMS_FETCH_ERROR',
                500,
                ['scope' => 'norms_list'],
                $e
            );
        }
    }

    public function createNorm(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        try {
            $norm = Norm::create($validated);
            return response()->json(['success' => true, 'message' => 'Norme créée', 'data' => $norm], 201);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la création de la norme.',
                'SUPERADMIN_NORM_CREATE_ERROR',
                500,
                ['scope' => 'norm_create'],
                $e
            );
        }
    }

    public function getOffers()
    {
        try {
            $offers = Offer::with('norms')->orderBy('created_at', 'desc')->get();
            return response()->json(['success' => true, 'data' => $offers]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des offres.',
                'SUPERADMIN_OFFERS_FETCH_ERROR',
                500,
                ['scope' => 'offers_list'],
                $e
            );
        }
    }

    /**
     * Download enterprise document (signed URL protection)
     */
    /**
     * Download a document using a signed URL (enterprise/document)
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $enterpriseId
     * @param int|string $documentId
     * @return mixed
     */
    public function downloadDocument(Request $request, $enterpriseId, $documentId)
    {
        // Vérifier la signature de l'URL
        if (!$request->hasValidSignature()) {
            return $this->errorResponse('URL invalide ou expirée.', 'SUPERADMIN_SIGNED_URL_INVALID', 403);
        }

        try {
            $document = EnterpriseDocument::where('enterprise_id', $enterpriseId)
                ->where('id', $documentId)
                ->firstOrFail();

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            if (!$disk->exists($document->stored_path)) {
                return $this->errorResponse('Fichier introuvable.', 'SUPERADMIN_FILE_NOT_FOUND', 404);
            }

            SecurityAuditLog::logEvent(
                eventType: 'document',
                action: 'download',
                resourceType: 'enterprise_document',
                resourceId: $document->id,
                metadata: [
                    'enterprise_id' => $enterpriseId,
                    'document_id' => $document->id,
                    'document_name' => $document->name,
                ],
                riskLevel: 'medium'
            );

            try {
                $mime = $disk->mimeType($document->stored_path) ?: 'application/octet-stream';
            } catch (\Exception $e) {
                $mime = 'application/octet-stream';
            }

            $headers = ExportHeaders::attachmentHeaders($document->name, $mime, false);

            try {
                $size = $disk->size($document->stored_path);
                $headers['Content-Length'] = (string) $size;
            } catch (\Exception $e) {
                // ignore
            }

            return $disk->download(
                $document->stored_path,
                $document->name,
                $headers
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Document introuvable.', 'SUPERADMIN_DOCUMENT_NOT_FOUND', 404);
        }
    }

    /**
     * Preview enterprise document (signed URL protection, inline)
     */
    /**
     * Preview a document inline using a signed URL (enterprise/document)
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $enterpriseId
     * @param int|string $documentId
     * @return mixed
     */
    public function previewDocument(Request $request, $enterpriseId, $documentId)
    {
        if (!$request->hasValidSignature()) {
            return $this->errorResponse('URL invalide ou expirée.', 'SUPERADMIN_SIGNED_URL_INVALID', 403);
        }

        try {
            $document = EnterpriseDocument::where('enterprise_id', $enterpriseId)
                ->where('id', $documentId)
                ->firstOrFail();

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            if (!$disk->exists($document->stored_path)) {
                return $this->errorResponse('Fichier introuvable.', 'SUPERADMIN_FILE_NOT_FOUND', 404);
            }

            $mime = $disk->mimeType($document->stored_path) ?: 'application/octet-stream';

            SecurityAuditLog::logEvent(
                eventType: 'document',
                action: 'preview',
                resourceType: 'enterprise_document',
                resourceId: $document->id,
                metadata: [
                    'enterprise_id' => $enterpriseId,
                    'document_id' => $document->id,
                    'document_name' => $document->name,
                ],
                riskLevel: 'low'
            );

            // Stream the file with explicit inline disposition and content-type to avoid browser forcing download
            $stream = $disk->readStream($document->stored_path);
            if ($stream === false) {
                return $this->errorResponse('Fichier introuvable.', 'SUPERADMIN_FILE_NOT_FOUND', 404);
            }

            $headers = ExportHeaders::attachmentHeaders($document->name, $mime, true);

            // Add Content-Length when available
            try {
                $size = $disk->size($document->stored_path);
            } catch (\Exception $ex) {
                $size = null;
            }

            if ($size !== null) {
                $headers['Content-Length'] = (string) $size;
            }

            return response()->stream(function () use ($stream) {
                // Output the stream to the response
                if (is_resource($stream)) {
                    while (!feof($stream)) {
                        echo fread($stream, 1024 * 8);
                        flush();
                    }
                    fclose($stream);
                }
            }, 200, $headers);
        } catch (\Exception $e) {
            return $this->errorResponse('Document introuvable.', 'SUPERADMIN_DOCUMENT_NOT_FOUND', 404);
        }
    }

    /**
     * Get single offer
     */
    /**
     * Get an offer by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffer($id)
    {
        try {
            $offer = Offer::with('norms')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $offer]);
        } catch (\Exception $e) {
            return $this->errorResponse('Offre non trouvée.', 'SUPERADMIN_OFFER_NOT_FOUND', 404);
        }
    }

    /**
     * Create new offer
     */
    public function createOffer(Request $request)
    {
        Log::info('CreateOffer called', [
            'user' => Auth::id(),
            'data' => $request->all()
        ]);

        $validated = $request->validate([
            'ref' => 'nullable|string|max:50|unique:offers,ref',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'norms' => 'required|array|min:1', // Array of norm IDs
            'norms.*' => 'exists:norms,id',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $ref = $validated['ref'] ?? null;
            if (!$ref) {
                $firstNormId = $validated['norms'][0] ?? null;
                if ($firstNormId) {
                    $norm = Norm::find($firstNormId);
                    if ($norm && preg_match('/ISO\\s*([0-9]{4,5})/i', $norm->code, $matches)) {
                        $baseRef = 'OFF-ISO' . $matches[1] . '-' . date('Y');
                        $refCandidate = $baseRef;
                        $suffix = 1;
                        while (Offer::where('ref', $refCandidate)->exists()) {
                            $suffix += 1;
                            $refCandidate = $baseRef . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
                        }
                        $ref = $refCandidate;
                    }
                }
            }

            $offer = Offer::create([
                'ref' => $ref,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? 'Description à compléter',
                'price' => $validated['price'],
                'duration_months' => $validated['duration_months'],
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            // Attach norms to the offer via pivot table
            $offer->norms()->attach($validated['norms']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offre créée avec succès',
                'data' => $offer->load('norms')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Une erreur est survenue lors de la création de l’offre.',
                'SUPERADMIN_OFFER_CREATE_ERROR',
                500,
                ['payload' => $validated],
                $e
            );
        }
    }

    /**
     * Update offer
     */
    /**
     * Update an offer by id.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOffer(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'norms' => 'required|array|min:1', // Array of norm IDs
            'norms.*' => 'exists:norms,id',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
        ]);

        try {
            $offer = Offer::findOrFail($id);

            DB::beginTransaction();

            $offer->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'duration_months' => $validated['duration_months'],
            ]);

            // Sync norms (remove old, add new)
            $offer->norms()->sync($validated['norms']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offre mise à jour avec succès',
                'data' => $offer->load('norms')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Une erreur est survenue lors de la mise à jour de l’offre.',
                'SUPERADMIN_OFFER_UPDATE_ERROR',
                500,
                ['offer_id' => $id],
                $e
            );
        }
    }

    /**
     * Delete offer
     */
    /**
     * Delete an offer by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOffer($id)
    {
        try {
            $offer = Offer::findOrFail($id);

            // Check if offer is used by any subscriptions
            $subscriptionsCount = EnterpriseSubscription::where('offer_id', $id)->count();
            if ($subscriptionsCount > 0) {
                return $this->errorResponse(
                    "Impossible de supprimer cette offre car elle est utilisée par {$subscriptionsCount} abonnement(s)",
                    'SUPERADMIN_OFFER_DELETE_BLOCKED_ACTIVE_SUBSCRIPTIONS',
                    422
                );
            }

            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Offre supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression de l’offre.',
                'SUPERADMIN_OFFER_DELETE_ERROR',
                500,
                ['offer_id' => $id],
                $e
            );
        }
    }

    // Subscriptions Management
    public function getSubscriptions(Request $request)
    {
        try {
            $query = EnterpriseSubscription::with(['site.enterprise', 'offer.norms']);
            $now = now();

            // Filters
            if ($request->status && $request->status !== 'all') {
                if ($request->status === 'active') {
                    $query
                        ->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('status')
                                ->orWhereNotIn('status', ['expired', 'cancelled']);
                        })
                        ->where(function ($q) use ($now) {
                            $q->whereNull('expiration_date')
                                ->orWhere('expiration_date', '>=', $now);
                        });
                } elseif ($request->status === 'suspended') {
                    $query
                        ->where('is_active', false)
                        ->where(function ($q) {
                            $q->whereNull('status')
                                ->orWhereNotIn('status', ['expired', 'cancelled']);
                        });
                } elseif ($request->status === 'expired') {
                    $query->where(function ($q) use ($now) {
                        $q->where('status', 'expired')
                            ->orWhere(function ($expiredQuery) use ($now) {
                                $expiredQuery
                                    ->whereNotNull('expiration_date')
                                    ->where('expiration_date', '<', $now)
                                    ->where('status', '!=', 'cancelled');
                            });
                    });
                } elseif ($request->status === 'cancelled') {
                    $query->where('status', 'cancelled');
                }
            }

            if ($request->enterprise_id) {
                $query->whereHas('site', function ($q) use ($request) {
                    $q->where('enterprise_id', $request->enterprise_id);
                });
            }

            if ($request->search) {
                $query->whereHas('site.enterprise', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                });
            }

            $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);
            $normalizedSubscriptions = $subscriptions->getCollection()->map(
                fn (EnterpriseSubscription $subscription) => $this->normalizeSubscriptionForResponse($subscription, $now)
            );

            return response()->json(['success' => true, 'data' => $normalizedSubscriptions->values(), 'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'total' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage()
            ]]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des abonnements.',
                'SUPERADMIN_SUBSCRIPTIONS_FETCH_ERROR',
                500,
                ['scope' => 'subscriptions_list'],
                $e
            );
        }
    }

    /**
     * Get a subscription by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscription($id)
    {
        try {
            $subscription = EnterpriseSubscription::with(['site.enterprise', 'offer.norms'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->normalizeSubscriptionForResponse($subscription, now()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Abonnement non trouvé.', 'SUPERADMIN_SUBSCRIPTION_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement de l’abonnement.',
                'SUPERADMIN_SUBSCRIPTION_FETCH_ERROR',
                500,
                ['subscription_id' => $id],
                $e
            );
        }
    }

    /**
     * Suspend a subscription with a reason.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspendSubscription(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|min:10']);

        try {
            $subscription = EnterpriseSubscription::findOrFail($id);
            $subscription->update([
                'is_active' => false
            ]);
            $normalizedStatus = 'suspended';
            $subscription->setAttribute('status', $normalizedStatus);
            $subscription->setAttribute('end_date', optional($subscription->expiration_date)?->toIso8601String());

            return response()->json([
                'success' => true,
                'message' => 'Abonnement suspendu',
                'data' => $this->normalizeSubscriptionForResponse(
                    $subscription->load(['site.enterprise', 'offer.norms']),
                    now()
                )
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suspension de l’abonnement.',
                'SUPERADMIN_SUBSCRIPTION_SUSPEND_ERROR',
                500,
                ['subscription_id' => $id],
                $e
            );
        }
    }

    /**
     * Reactivate a subscription by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reactivateSubscription($id)
    {
        try {
            $subscription = EnterpriseSubscription::findOrFail($id);
            $subscription->update([
                'is_active' => true,
                'status' => 'active',
            ]);
            $subscription->setAttribute('end_date', optional($subscription->expiration_date)?->toIso8601String());

            return response()->json([
                'success' => true,
                'message' => 'Abonnement réactivé',
                'data' => $this->normalizeSubscriptionForResponse(
                    $subscription->load(['site.enterprise', 'offer.norms']),
                    now()
                )
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la réactivation de l’abonnement.',
                'SUPERADMIN_SUBSCRIPTION_REACTIVATE_ERROR',
                500,
                ['subscription_id' => $id],
                $e
            );
        }
    }

    /**
     * Delete a subscription by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubscription($id)
    {
        try {
            $subscription = EnterpriseSubscription::findOrFail($id);

            // Check if subscription is active
            if ($subscription->is_active) {
                return $this->errorResponse(
                    'Impossible de supprimer un abonnement actif. Veuillez d\'abord le suspendre.',
                    'SUPERADMIN_SUBSCRIPTION_DELETE_BLOCKED_ACTIVE',
                    422
                );
            }

            $subscription->delete();

            return response()->json(['success' => true, 'message' => 'Abonnement supprimé']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression de l’abonnement.',
                'SUPERADMIN_SUBSCRIPTION_DELETE_ERROR',
                500,
                ['subscription_id' => $id],
                $e
            );
        }
    }

    private function normalizeSubscriptionForResponse(EnterpriseSubscription $subscription, $now): EnterpriseSubscription
    {
        $status = (string) ($subscription->status ?? '');
        $isExpired = $status === 'expired'
            || ($subscription->expiration_date && $subscription->expiration_date->lt($now));

        if ($status === 'cancelled') {
            $normalizedStatus = 'cancelled';
        } elseif ($isExpired) {
            $normalizedStatus = 'expired';
        } elseif (!$subscription->is_active) {
            $normalizedStatus = 'suspended';
        } else {
            $normalizedStatus = 'active';
        }

        $subscription->setAttribute('status', $normalizedStatus);
        $subscription->setAttribute('end_date', optional($subscription->expiration_date)?->toIso8601String());

        return $subscription;
    }

    /**
     * Get all platform users with statistics
     */
    public function getAllUsers(Request $request)
    {
        try {
            $query = User::with(['enterprise', 'site', 'roles']);

            // Filter by user type
            if ($request->has('user_type') && $request->user_type !== 'all') {
                $query->where('user_type', $request->user_type);
            }

            // Filter by active status
            if ($request->has('is_active') && $request->is_active !== 'all') {
                $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
            }

            // Search by name, email, or username
            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('username', 'like', '%' . $request->search . '%');
                });
            }

            // Filter by enterprise
            if ($request->has('enterprise_id') && $request->enterprise_id) {
                $query->where('enterprise_id', $request->enterprise_id);
            }

            $perPage = $request->get('per_page', 20);
            $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des utilisateurs.',
                'SUPERADMIN_USERS_FETCH_ERROR',
                500,
                ['scope' => 'users_list'],
                $e
            );
        }
    }

    /**
     * Get users statistics
     */
    public function getUsersStats()
    {
        try {
            $stats = [
                'total' => User::count(),
                'by_type' => [
                    'super_admin' => User::where('user_type', 'super_admin')->count(),
                    'company' => User::where('user_type', 'company')->count(),
                    'clientb' => User::where('user_type', 'clientb')->count(),
                ],
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'client_b_total' => User::where('user_type', 'clientb')->count(),
                'recent' => [
                    'today' => User::whereDate('created_at', today())->count(),
                    'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ],
            ];

            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des statistiques utilisateurs.',
                'SUPERADMIN_USERS_STATS_ERROR',
                500,
                ['scope' => 'users_stats'],
                $e
            );
        }
    }

    /**
     * Uniform error JSON response helper.
     *
     * @param string $message
     * @param string $errorCode
     * @param int $status
     * @param array $context
     * @param \Throwable|null $exception
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse(
        string $message,
        string $errorCode,
        int $status,
        array $context = [],
        ?\Throwable $exception = null
    ) {
        /** @var \Illuminate\Http\Request|null $request */
        $request = request();
        $request = request();
        $correlationId = (string) ($request?->header('X-Request-Id') ?: Str::uuid());

        if ($status >= 500 || $exception) {
            Log::error($errorCode, array_merge($context, [
                'correlation_id' => $correlationId,
                'status' => $status,
                'exception' => $exception ? [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
            ]));
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'correlation_id' => $correlationId,
        ], $status);
    }
}
