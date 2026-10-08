<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Core\DynamicFieldService;
use App\Services\Core\NotificationCenterService;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationRequest;
use App\Models\EvaluationResponse;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvaluationRequestController extends Controller
{
    public function __construct(
        private readonly DynamicFieldService $dynamicFieldService,
        private readonly NotificationCenterService $notificationCenterService,
    ) {}

    /**
     * Liste des demandes d'évaluation
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $enterpriseId = $user->enterprise_id;

        $query = EvaluationRequest::where('enterprise_id', $enterpriseId)
            ->with(['site', 'createdBy', 'response']);

        // Filtres
        if ($request->has('type') && $request->type) {
            $query->forType($request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('site_id') && $request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        // Tri
        $query->orderByDesc('created_at');

        // Pagination
        $perPage = $request->input('per_page', 20);
        $requests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Afficher une demande
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $request = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->with(['site', 'createdBy', 'response', 'requestable'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $request,
        ]);
    }

    /**
     * Créer une nouvelle demande
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $siteRule = $user->isSuperAdmin()
            ? 'nullable|exists:sites,id'
            : ['nullable', Rule::exists('sites', 'id')->where('enterprise_id', $user->enterprise_id)];

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:satisfaction_client,satisfaction_personnel,performance_personnel,evaluation_personnel,evaluation_auditeur,satisfaction_fournisseur,performance_fournisseur,evaluation_fournisseur,audit_interne',
            'recipient_email' => 'nullable|email',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_company' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'site_id' => $siteRule,
            'requestable_type' => 'nullable|string',
            'requestable_id' => 'nullable|integer',
            'send_immediately' => 'nullable|boolean',
            'criteria_ids' => 'nullable|array|min:1',
            'criteria_ids.*' => 'integer|exists:evaluation_criteria,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['enterprise_id'] = $user->enterprise_id;
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';
        unset($data['criteria_ids']);

        if ($this->isAnonymousSatisfactionRequestType((string) $data['type'])) {
            $data['recipient_email'] = null;
            $data['recipient_name'] = null;
        } elseif (empty($data['recipient_email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => [
                    'recipient_email' => ['Le champ recipient_email est requis pour ce type de demande.'],
                ],
            ], 422);
        }

        $criteriaIds = $request->input('criteria_ids');

        if (is_array($criteriaIds) && !$this->selectedCriteriaAreValid($criteriaIds, $user->enterprise_id, $request->input('type'))) {
            return response()->json([
                'success' => false,
                'message' => 'Les criteres selectionnes ne correspondent pas a l entreprise ou au type de demande',
            ], 422);
        }

        $evaluationRequest = EvaluationRequest::create($data);
        $this->dynamicFieldService->syncRequestCriteriaSnapshot($evaluationRequest, $criteriaIds);

        // Envoyer immédiatement si demandé
        if ($request->boolean('send_immediately') && !$this->isAnonymousSatisfactionRequestType((string) $evaluationRequest->type)) {
            $this->sendRequest($evaluationRequest);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande créée avec succès',
            'data' => $evaluationRequest->load(['site', 'createdBy']),
            'public_url' => $evaluationRequest->getPublicUrl(),
        ], 201);
    }

    /**
     * Mettre à jour une demande
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->whereIn('status', ['draft', 'pending'])
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:satisfaction_client,satisfaction_personnel,performance_personnel,evaluation_personnel,evaluation_auditeur,satisfaction_fournisseur,performance_fournisseur,evaluation_fournisseur,audit_interne,custom',
            'recipient_email' => 'sometimes|nullable|email',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_company' => 'nullable|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'message' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'criteria_ids' => 'nullable|array|min:1',
            'criteria_ids.*' => 'integer|exists:evaluation_criteria,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $criteriaIds = $validated['criteria_ids'] ?? null;
        unset($validated['criteria_ids']);

        if (is_array($criteriaIds) && !$this->selectedCriteriaAreValid($criteriaIds, $user->enterprise_id, $validated['type'] ?? $evaluationRequest->type)) {
            return response()->json([
                'success' => false,
                'message' => 'Les criteres selectionnes ne correspondent pas a l entreprise ou au type de demande',
            ], 422);
        }

        $resolvedType = (string) ($validated['type'] ?? $evaluationRequest->type);
        if ($this->isAnonymousSatisfactionRequestType($resolvedType)) {
            $validated['recipient_email'] = null;
            $validated['recipient_name'] = null;
        } else {
            $resolvedRecipientEmail = $validated['recipient_email'] ?? $evaluationRequest->recipient_email;
            if (empty($resolvedRecipientEmail)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => [
                        'recipient_email' => ['Le champ recipient_email est requis pour ce type de demande.'],
                    ],
                ], 422);
            }
        }

        $evaluationRequest->update($validated);

        if ($request->has('criteria_ids')) {
            $this->dynamicFieldService->syncRequestCriteriaSnapshot($evaluationRequest->fresh(), $criteriaIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour',
            'data' => $evaluationRequest->fresh(['site', 'createdBy']),
        ]);
    }

    /**
     * Supprimer une demande
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        $evaluationRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande supprimée',
        ]);
    }

    /**
     * Envoyer une demande par email
     */
    public function send(int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->whereIn('status', ['draft', 'pending'])
            ->findOrFail($id);

        try {
            $this->sendRequest($evaluationRequest);
        } catch (ValidationException $validationException) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validationException->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande envoyée',
            'data' => $evaluationRequest->fresh(),
        ]);
    }

    /**
     * Envoyer une relance
     */
    public function sendReminder(int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->whereIn('status', ['sent', 'opened'])
            ->findOrFail($id);

        try {
            $this->sendRequest($evaluationRequest, true);
        } catch (ValidationException $validationException) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validationException->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Relance envoyée',
            'data' => $evaluationRequest->fresh(),
        ]);
    }

    /**
     * Annuler une demande
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->findOrFail($id);

        $evaluationRequest->cancel();
        activity()
            ->causedBy($user)
            ->performedOn($evaluationRequest)
            ->withProperties([
                'action' => 'cancel',
                'site_id' => $evaluationRequest->site_id,
                'recipient_email' => $evaluationRequest->recipient_email,
            ])
            ->log('evaluation_request.cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Demande annulée',
            'data' => $evaluationRequest,
        ]);
    }

    /**
     * Statistiques des demandes
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = EvaluationRequest::where('enterprise_id', $user->enterprise_id);

        if ($request->has('type') && $request->type) {
            $query->forType($request->type);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'opened' => (clone $query)->where('status', 'opened')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'expired' => (clone $query)->where('status', 'expired')->count(),
            'response_rate' => 0,
        ];

        $byType = (clone $query)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $completedRows = (clone $query)
            ->where('status', 'completed')
            ->with('response')
            ->get(['id', 'sent_at', 'responded_at']);

        $averageScore = $completedRows
            ->filter(fn ($row) => $row->response && $row->response->percentage !== null)
            ->avg(fn ($row) => (float) $row->response->percentage);

        $averageResponseTimeHours = $completedRows
            ->filter(fn ($row) => $row->sent_at && $row->responded_at)
            ->map(function ($row) {
                return $row->responded_at->diffInHours($row->sent_at);
            })
            ->avg();

        $totalSent = $stats['sent'] + $stats['opened'] + $stats['completed'];
        if ($totalSent > 0) {
            $stats['response_rate'] = round(($stats['completed'] / $totalSent) * 100, 1);
        }

        $stats['total_requests'] = $stats['total'];
        $stats['by_status'] = [
            'draft' => $stats['draft'],
            'sent' => $stats['sent'],
            'opened' => $stats['opened'],
            'completed' => $stats['completed'],
            'expired' => $stats['expired'],
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
        $stats['by_form_type'] = $byType;
        $stats['average_score'] = $averageScore !== null ? round((float) $averageScore, 1) : null;
        $stats['average_response_time_hours'] = $averageResponseTimeHours !== null
            ? round((float) $averageResponseTimeHours, 1)
            : null;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Liste les reponses associees a une demande.
     */
    public function responses(int $id): JsonResponse
    {
        $user = Auth::user();
        $evaluationRequest = EvaluationRequest::where('enterprise_id', $user->enterprise_id)
            ->with(['responses', 'criteria'])
            ->findOrFail($id);

        $criteriaMap = $evaluationRequest->criteria
            ->keyBy('id');

        $data = $evaluationRequest->responses
            ->map(function (EvaluationResponse $response) use ($criteriaMap) {
                $scores = collect($response->responses ?? [])->mapWithKeys(fn ($item, $criterionId) => [
                    (int) $criterionId => $item['score'] ?? null,
                ])->toArray();

                $comments = collect($response->responses ?? [])->mapWithKeys(fn ($item, $criterionId) => [
                    (int) $criterionId => $item['comment'] ?? null,
                ])->filter(fn ($comment) => filled($comment))->toArray();

                $scoreRows = collect($response->responses ?? [])
                    ->map(function ($item, $criterionId) use ($criteriaMap) {
                        $criterionIdInt = (int) $criterionId;
                        $criterion = $criteriaMap->get($criterionIdInt);

                        return [
                            'criterion_id' => $criterionIdInt,
                            'criterion_name' => $criterion?->name ?? "Critere {$criterionIdInt}",
                            'score' => isset($item['score']) ? (float) $item['score'] : null,
                            'comment' => $item['comment'] ?? null,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $response->id,
                    'evaluation_request_id' => $response->evaluation_request_id,
                    'respondent_name' => null,
                    'respondent_email' => null,
                    'scores' => $scores,
                    'comments' => $comments,
                    'score_rows' => $scoreRows,
                    'global_comment' => $response->general_comment,
                    'overall_score' => $response->percentage,
                    'submitted_at' => $response->created_at,
                    'created_at' => $response->created_at,
                    'updated_at' => $response->updated_at,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Méthode interne pour envoyer l'email
     */
    private function sendRequest(EvaluationRequest $request, bool $isReminder = false): void
    {
        if (blank($request->recipient_email)) {
            throw ValidationException::withMessages([
                'recipient_email' => ['Cette demande ne contient pas d’adresse email destinataire.'],
            ]);
        }

        try {
            $this->notificationCenterService->sendEvaluationRequest($request, $isReminder);

            if ($isReminder) {
                $request->incrementReminderCount();
            } else {
                $request->markAsSent();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send evaluation request email', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Public routes (no auth required)
    |--------------------------------------------------------------------------
    */

    /**
     * Accéder au formulaire public via token
     */
    public function showPublic(string $token): JsonResponse
    {
        $evaluationRequest = EvaluationRequest::where('token', $token)
            ->with(['site'])
            ->firstOrFail();

        // Le formulaire reste accessible tant qu'il n'est ni expire ni annule
        if ($evaluationRequest->status === 'cancelled' || $evaluationRequest->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => $evaluationRequest->isExpired()
                    ? 'Cette demande a expiré'
                    : 'Cette demande a été annulée',
                'status' => $evaluationRequest->status,
            ], 410);
        }

        // Marquer comme ouvert
        $evaluationRequest->markAsOpened();

        // Charger les criteres figes de la demande (fallback sur le catalogue si migration non executee)
        $criteria = $this->dynamicFieldService->resolveRequestCriteria($evaluationRequest);

        return response()->json([
            'success' => true,
            'data' => [
                'request' => [
                    'type' => $evaluationRequest->type,
                    'type_label' => $evaluationRequest->type_label,
                    'subject' => $evaluationRequest->subject,
                    'message' => $evaluationRequest->message,
                    'site' => $evaluationRequest->site ? [
                        'name' => $evaluationRequest->site->name,
                    ] : null,
                ],
                'criteria' => $criteria,
            ],
        ]);
    }

    /**
     * Soumettre une réponse via token
     */
    public function submitPublic(Request $request, string $token): JsonResponse
    {
        return DB::transaction(function () use ($request, $token): JsonResponse {
            $evaluationRequest = EvaluationRequest::where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($evaluationRequest->status === 'cancelled' || $evaluationRequest->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => $evaluationRequest->isExpired()
                        ? 'Cette demande a expiré'
                        : 'Cette demande a été annulée',
                ], 410);
            }

            $validator = Validator::make($request->all(), [
                'respondent_name' => 'nullable|string|max:255',
                'respondent_email' => 'nullable|email|max:255',
                'responses' => 'required|array|min:1',
                'responses.*.criterion_id' => 'required|integer|distinct',
                'responses.*.score' => 'required|numeric|min:0|max:100',
                'responses.*.comment' => 'nullable|string',
                'general_comment' => 'nullable|string',
                'recommendations' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $criteriaById = $this->dynamicFieldService
                ->resolveRequestCriteria($evaluationRequest)
                ->keyBy('id');

            $errors = [];
            $formattedResponses = [];

            foreach ($validated['responses'] as $index => $responseItem) {
                $criterionId = (int) $responseItem['criterion_id'];
                /** @var EvaluationCriteria|null $criterion */
                $criterion = $criteriaById->get($criterionId);

                if (!$criterion) {
                    $errors["responses.{$index}.criterion_id"][] = 'Ce critere ne fait pas partie de cette demande.';
                    continue;
                }

                $minScore = (float) ($criterion->scale_min ?? 0);
                $maxScore = (float) ($criterion->scale_max ?? 5);
                $score = (float) $responseItem['score'];

                if ($score < $minScore || $score > $maxScore) {
                    $errors["responses.{$index}.score"][] = "Le score doit etre compris entre {$minScore} et {$maxScore}.";
                    continue;
                }

                $formattedResponses[$criterionId] = [
                    'score' => $score,
                    'comment' => $responseItem['comment'] ?? null,
                ];
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $errors,
                ], 422);
            }

            // Créer une nouvelle réponse à chaque soumission publique
            $evaluationResponse = EvaluationResponse::query()->create([
                'evaluation_request_id' => $evaluationRequest->id,
                'responses' => $formattedResponses,
                'general_comment' => $validated['general_comment'] ?? null,
                'recommendations' => $validated['recommendations'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Calculer les scores
            $criteria = $criteriaById
                ->map(fn (EvaluationCriteria $criterion) => [
                    'id' => $criterion->id,
                    'weight' => $criterion->weight,
                    'scale_max' => $criterion->scale_max,
                ])
                ->values()
                ->all();

            $evaluationResponse->calculateScores($criteria);

            $overallScore = count($formattedResponses) > 0
                ? round(((float) $evaluationResponse->total_score) / count($formattedResponses), 2)
                : null;

            // Marquer la demande comme complétée
            $evaluationRequest->markAsCompleted();
            $this->syncSatisfactionSurveyFromPublicSubmission(
                $evaluationRequest,
                $evaluationResponse,
                $formattedResponses,
                $validated,
                $criteriaById->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Merci pour votre réponse !',
                'data' => [
                    'overall_score' => $overallScore,
                    'percentage' => $evaluationResponse->percentage,
                    'satisfaction_level' => $evaluationResponse->satisfaction_level_label,
                ],
            ]);
        });
    }

    /**
     * Regeneration publique d'un lien d'evaluation expire.
     */
    public function requestPublicLinkReset(Request $request, string $token): JsonResponse
    {
        return DB::transaction(function () use ($request, $token): JsonResponse {
            $evaluationRequest = EvaluationRequest::where('token', $token)
                ->lockForUpdate()
                ->with('criteria')
                ->firstOrFail();

            $metadata = is_array($evaluationRequest->metadata) ? $evaluationRequest->metadata : [];
            $now = now();
            $cooldownSeconds = 60;
            $maxPerDay = 3;

            $lastResetAt = isset($metadata['last_public_reset_requested_at'])
                ? \Carbon\Carbon::parse($metadata['last_public_reset_requested_at'])
                : null;

            if ($lastResetAt && $lastResetAt->diffInSeconds($now) < $cooldownSeconds) {
                Log::warning('Evaluation public reset throttled by cooldown', [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'enterprise_id' => $evaluationRequest->enterprise_id,
                    'site_id' => $evaluationRequest->site_id,
                    'ip' => (string) $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Votre demande a bien ete prise en compte.',
                    'retry_after_seconds' => $cooldownSeconds - $lastResetAt->diffInSeconds($now),
                ], 429);
            }

            $dailyRequests = collect($metadata['public_reset_requests'] ?? [])
                ->filter(fn ($entry) => isset($entry['at']) && \Carbon\Carbon::parse($entry['at'])->isSameDay($now))
                ->values();

            if ($dailyRequests->count() >= $maxPerDay) {
                Log::warning('Evaluation public reset throttled by daily limit', [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'enterprise_id' => $evaluationRequest->enterprise_id,
                    'site_id' => $evaluationRequest->site_id,
                    'ip' => (string) $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Votre demande a bien ete prise en compte.',
                ], 429);
            }

            $newRequest = $evaluationRequest->replicate([
                'token',
                'status',
                'sent_at',
                'opened_at',
                'responded_at',
                'last_reminder_at',
                'reminder_count',
                'created_at',
                'updated_at',
            ]);

            $newRequest->token = (string) Str::uuid();
            // HasReference génère une nouvelle référence lors de la création si la valeur est vide.
            $newRequest->ref = null;
            $newRequest->status = 'sent';
            $newRequest->sent_at = $now;
            $newRequest->opened_at = null;
            $newRequest->responded_at = null;
            $newRequest->reminder_count = 0;
            $newRequest->last_reminder_at = null;
            $newRequest->expires_at = ($evaluationRequest->expires_at && $evaluationRequest->expires_at->isFuture())
                ? $evaluationRequest->expires_at
                : $now->copy()->addDays(14);
            $newRequest->metadata = array_merge($metadata, [
                'regenerated_from_token' => $evaluationRequest->token,
                'regenerated_at' => $now->toDateTimeString(),
            ]);
            $newRequest->save();

            // Copier les criteres figes de la demande precedente
            $criteriaPayload = $evaluationRequest->criteria->mapWithKeys(function ($criterion) {
                return [
                    (int) $criterion->id => [
                        'criterion_name' => $criterion->pivot->criterion_name,
                        'criterion_code' => $criterion->pivot->criterion_code,
                        'criterion_description' => $criterion->pivot->criterion_description,
                        'criterion_category' => $criterion->pivot->criterion_category,
                        'scale_type' => $criterion->pivot->scale_type,
                        'scale_min' => $criterion->pivot->scale_min,
                        'scale_max' => $criterion->pivot->scale_max,
                        'scale_labels' => $criterion->pivot->scale_labels,
                        'weight' => $criterion->pivot->weight,
                        'is_mandatory' => $criterion->pivot->is_mandatory,
                        'display_order' => $criterion->pivot->display_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            })->toArray();

            if (!empty($criteriaPayload)) {
                $newRequest->criteria()->attach($criteriaPayload);
            }

            $requestHistory = collect($metadata['public_reset_requests'] ?? [])
                ->push([
                    'at' => $now->toDateTimeString(),
                    'ip' => (string) $request->ip(),
                    'user_agent' => (string) ($request->userAgent() ?? ''),
                ])
                ->values()
                ->all();

            $evaluationRequest->update([
                'status' => $evaluationRequest->isExpired() ? 'expired' : $evaluationRequest->status,
                'metadata' => array_merge($metadata, [
                    'last_public_reset_requested_at' => $now->toDateTimeString(),
                    'public_reset_requests' => $requestHistory,
                    'last_regenerated_token' => $newRequest->token,
                ]),
            ]);

            Log::info('Evaluation public reset link generated', [
                'evaluation_request_id' => $evaluationRequest->id,
                'new_evaluation_request_id' => $newRequest->id,
                'enterprise_id' => $evaluationRequest->enterprise_id,
                'site_id' => $evaluationRequest->site_id,
                'ip' => (string) $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre demande a bien ete prise en compte.',
                'data' => [
                    'new_public_url' => $newRequest->getPublicUrl(),
                    'expires_at' => optional($newRequest->expires_at)->toIso8601String(),
                ],
            ]);
        });
    }

    /**
     * Verifie que les criteres choisis appartiennent a l entreprise et au type de demande.
     */
    private function selectedCriteriaAreValid(array $criteriaIds, int $enterpriseId, string $requestType): bool
    {
        $uniqueIds = array_values(array_unique($criteriaIds));

        $count = EvaluationCriteria::where('enterprise_id', $enterpriseId)
            ->whereIn('id', $uniqueIds)
            ->where('form_type', $requestType)
            ->count();

        return $count === count($uniqueIds);
    }

    private function isAnonymousSatisfactionRequestType(string $requestType): bool
    {
        return $requestType === 'satisfaction_client';
    }

    /**
     * Synchronise la fiche de satisfaction (dashboard entreprise) depuis une soumission publique.
     */
    private function syncSatisfactionSurveyFromPublicSubmission(
        EvaluationRequest $evaluationRequest,
        EvaluationResponse $evaluationResponse,
        array $formattedResponses,
        array $validated,
        array $criteriaById = []
    ): void {
        $surveyType = $this->resolveSatisfactionSurveyType($evaluationRequest->type);
        if (!$surveyType) {
            return;
        }

        $siteId = (int) ($evaluationRequest->site_id ?? 0);
        if ($siteId <= 0) {
            throw new \RuntimeException('Aucun site défini pour cette demande de satisfaction.');
        }

        $responses = [];
        foreach ($formattedResponses as $criterionId => $response) {
            $criterionIdInt = (int) $criterionId;
            /** @var EvaluationCriteria|null $criterion */
            $criterion = $criteriaById[$criterionIdInt] ?? null;
            $normalizedKey = $this->normalizeCriterionKey($criterion, $criterionIdInt);
            $score = (float) ($response['score'] ?? 0);

            // Compatibilite: on conserve la cle fonctionnelle + l'identifiant numerique.
            $responses[$normalizedKey] = $score;
            $responses[(string) $criterionIdInt] = $score;
        }

        $traceability = [
            'source' => 'public_link',
            'evaluation_request_id' => $evaluationRequest->id,
            'evaluation_response_id' => $evaluationResponse->id,
            'token' => $evaluationRequest->token,
            'submitted_at' => now()->toDateTimeString(),
        ];

        $traceabilityField = match ($surveyType) {
            'employee' => 'm13_d3_traceability',
            'client' => 'm13_d4_traceability',
            'supplier' => 'm13_d6_traceability',
            default => null,
        };

        $surveyPayload = [
            'site_id' => $siteId,
            'type' => $surveyType,
            'year' => (int) now()->format('Y'),
            'period' => now()->format('m/Y'),
            'respondent_name' => $validated['respondent_name'] ?? $evaluationRequest->recipient_name,
            'respondent_email' => $validated['respondent_email'] ?? $evaluationRequest->recipient_email,
            'responses' => $responses,
            'total_score' => (float) ($evaluationResponse->total_score ?? 0),
            'satisfaction_level' => $evaluationResponse->satisfaction_level,
            'recommendations' => $validated['recommendations'] ?? $validated['general_comment'] ?? null,
        ];

        if ($traceabilityField) {
            $surveyPayload[$traceabilityField] = $traceability;
        }

        // Créer une nouvelle fiche à chaque soumission pour conserver l'historique complet
        $survey = SatisfactionSurvey::query()->create(array_merge($surveyPayload, [
            'created_by' => $evaluationRequest->created_by,
        ]));

        $metadata = $evaluationRequest->metadata ?? [];

        $metadata['satisfaction_survey_id'] = $survey->id;
        $metadata['last_public_submission_at'] = now()->toDateTimeString();

        $evaluationRequest->update([
            'requestable_type' => SatisfactionSurvey::class,
            'requestable_id' => $survey->id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mappe le type de demande vers le type de fiche de satisfaction.
     */
    private function resolveSatisfactionSurveyType(string $requestType): ?string
    {
        return match ($requestType) {
            'satisfaction_client' => 'client',
            'satisfaction_personnel' => 'employee',
            'satisfaction_fournisseur' => 'supplier',
            default => null,
        };
    }

    /**
     * Reproduit la cle frontend (code/name normalise) pour les reponses de criteres.
     */
    private function normalizeCriterionKey(?EvaluationCriteria $criterion, int $criterionId): string
    {
        $raw = trim((string) ($criterion?->code ?: ($criterion?->name ?: "criterion_{$criterionId}")));
        $normalized = strtolower(preg_replace('/\s+/', '_', $raw) ?: '');

        return $normalized !== '' ? $normalized : "criterion_{$criterionId}";
    }

}
