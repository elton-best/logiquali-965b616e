<?php

namespace App\Modules\Processes\Controllers;

use App\Models\Document;
use App\Models\EvaluationRequest;
use App\Models\EvaluationResponse;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Process;
use App\Models\ProcessReview;
use App\Models\User;
use App\Services\DocumentSyncService;
use App\Services\ProcessReviewReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProcessReviewController extends Controller
{
    public function __construct(
        private readonly ProcessReviewReportService $reportService,
        private readonly DocumentSyncService $inventorySyncService,
    ) {
        // L'accès fonctionnel est géré via ensureAccess() pour éviter les faux 403
        // lorsque la matrice RBAC n'est pas encore resynchronisée sur toutes les bases.
    }

    public function current(Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'read');

        $review = $this->resolveCurrentReview($process, $user);
        $review->setAttribute('computed_metrics', $this->computeMetrics($process));
        $review->setAttribute('capabilities', $this->buildCapabilities($process, $user));

        return response()->json([
            'success' => true,
            'data' => $review->load(['leader:id,name', 'closedBy:id,name']),
        ]);
    }

    public function upsertCurrent(Request $request, Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'update');

        $validated = $request->validate([
            'identification' => ['nullable', 'array'],
            'identification.rq_name' => ['nullable', 'string', 'max:255'],
            'identification.include_pilot' => ['nullable', 'boolean'],
            'identification.include_copilot' => ['nullable', 'boolean'],
            'identification.present_user_ids' => ['nullable', 'array'],
            'identification.present_user_ids.*' => ['integer', 'exists:users,id'],
            'identification.present_others' => ['nullable', 'string'],
            'identification.coverage_start' => ['nullable', 'date'],
            'identification.coverage_end' => ['nullable', 'date'],
            'identification.started_at' => ['nullable', 'string'],
            'identification.ended_at' => ['nullable', 'string'],

            'sections' => ['nullable', 'array'],
            'sections.pip_summary' => ['nullable', 'string'],
            'sections.risk_opportunity_summary' => ['nullable', 'string'],
            'sections.objectives_projects_summary' => ['nullable', 'string'],
            'sections.compliance_nc_satisfaction_summary' => ['nullable', 'string'],
            'sections.management_duerp_display' => ['nullable', 'string'],

            'metrics_snapshot' => ['nullable', 'array'],
            'status' => ['nullable', 'in:planned,in_progress'],
            'review_date' => ['nullable', 'date'],
            'next_review_date' => ['nullable', 'date'],
        ]);

        if (array_key_exists('identification', $validated) && !$this->canEditIdentification($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Responsable Qualité (RQ) peut modifier la section Identification.',
            ], 403);
        }

        $review = $this->resolveCurrentReview($process, $user);
        if ($review->status === ProcessReview::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cette revue est clôturée définitivement et ne peut plus être modifiée.',
            ], 422);
        }

        $payload = [];
        if (array_key_exists('identification', $validated)) {
            $payload['identification'] = $validated['identification'];
            $payload['participants'] = $validated['identification']['present_user_ids'] ?? [];
        }
        if (array_key_exists('sections', $validated)) {
            $payload['sections'] = $validated['sections'];
        }
        if (array_key_exists('metrics_snapshot', $validated)) {
            $payload['metrics_snapshot'] = $validated['metrics_snapshot'];
        }
        if (array_key_exists('review_date', $validated)) {
            $payload['review_date'] = $validated['review_date'];
        }
        if (array_key_exists('next_review_date', $validated)) {
            $payload['next_review_date'] = $validated['next_review_date'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }

        if ($review->status === ProcessReview::STATUS_PLANNED) {
            $payload['status'] = ProcessReview::STATUS_IN_PROGRESS;
            $payload['started_at'] = $review->started_at ?? now();
        }

        if (!empty($payload)) {
            $review->update($payload);
        }

        activity()
            ->causedBy($user)
            ->performedOn($review)
            ->withProperties([
                'process_id' => $process->id,
                'updated_fields' => array_keys($payload),
            ])
            ->log('process_review.updated');

        return response()->json([
            'success' => true,
            'message' => 'Revue processus enregistrée.',
            'data' => $review->fresh(['leader:id,name', 'closedBy:id,name'])->setAttribute(
                'computed_metrics',
                $this->computeMetrics($process)
            )->setAttribute('capabilities', $this->buildCapabilities($process, $user)),
        ]);
    }

    public function closeCurrent(Request $request, Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'update');

        $review = $this->resolveCurrentReview($process, $user);
        if ($review->status === ProcessReview::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'La revue est déjà clôturée.',
            ], 422);
        }

        // REQ-9.2-09 : Vérifier les actions en retard non replanifiées
        $pendingOverdueActions = Action::where('process_id', $process->id)
            ->whereNotIn('status', ['completed', 'verified', 'cancelled'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now())
            ->whereNull('m7_d4_traceability->replan_decision')
            ->count();

        if ($pendingOverdueActions > 0 && !$request->boolean('force_close')) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de clôturer la revue : {$pendingOverdueActions} action(s) en retard n'ont pas encore fait l'objet d'une décision de replanification (REQ-9.2-09). Veuillez statuer sur ces actions ou confirmer avec force_close=true.",
                'pending_overdue_count' => $pendingOverdueActions,
            ], 422);
        }

        $review->update([
            'status' => ProcessReview::STATUS_COMPLETED,
            'ended_at' => now(),
            'closed_by' => $user->id,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($review)
            ->withProperties(['process_id' => $process->id])
            ->log('process_review.closed');

        return response()->json([
            'success' => true,
            'message' => 'Revue processus clôturée définitivement.',
            'data' => $review->fresh(['leader:id,name', 'closedBy:id,name'])->setAttribute(
                'computed_metrics',
                $this->computeMetrics($process)
            )->setAttribute('capabilities', $this->buildCapabilities($process, $user)),
        ]);
    }

    /**
     * Soumission des suggestions d'amélioration de la revue au RQ pour validation (REQ-9.2-11).
     */
    public function submitSuggestionsToRq(Request $request, Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'update');

        $validated = $request->validate([
            'suggestions' => ['required', 'array', 'min:1'],
            'suggestions.*.title' => ['required', 'string', 'max:255'],
            'suggestions.*.description' => ['required', 'string'],
            'suggestions.*.normes' => ['nullable', 'array'],
            'suggestions.*.assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $createdSuggestions = [];
        foreach ($validated['suggestions'] as $item) {
            $createdSuggestions[] = \App\Models\ImprovementSuggestion::create([
                'site_id' => $process->site_id,
                'process_id' => $process->id,
                'proposer_id' => $user->id,
                'assigned_to' => $item['assigned_to'] ?? null,
                'title' => $item['title'],
                'description' => $item['description'],
                'normes' => $item['normes'] ?? $process->normes_iso ?? [],
                'status' => 'pending', // Soumis au RQ
                'proposed_at' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdSuggestions) . ' suggestion(s) soumise(s) avec succès au Responsable Qualité (RQ).',
            'data' => $createdSuggestions,
        ]);
    }

    public function exportCurrentPdf(Process $process): BinaryFileResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'read');
        $review = $this->resolveCurrentReview($process, $user);

        $export = $this->reportService->buildPdf($review);
        $tempPath = storage_path('app/temp/' . $export['filename']);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $export['binary']);

        $document = $this->syncGeneratedReportToInventory($process, $user, $tempPath, 'pdf');

        $response = response()->download($tempPath, $export['filename'])->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function exportCurrentDocx(Process $process): BinaryFileResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'read');
        $review = $this->resolveCurrentReview($process, $user);

        $export = $this->reportService->buildDocx($review);
        $path = (string) $export['path'];

        $document = $this->syncGeneratedReportToInventory($process, $user, $path, 'docx');

        $response = response()->download($path, (string) $export['filename'])->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function linkedData(Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'read');

        return response()->json([
            'success' => true,
            'data' => $this->buildLinkedData($process),
        ]);
    }

    public function createLinkedAction(Request $request, Process $process): JsonResponse
    {
        $user = Auth::user();
        $this->ensureAccess($process, $user, 'update');

        $validated = $request->validate([
            'source_type' => ['required', 'in:duerp_danger,aes_aspect,reclamation'],
            'source_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['nullable', 'in:corrective,preventive,improvement,emergency,curative'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
        ]);

        $tenantContext = [
            'enterprise_id' => (int) ($process->enterprise_id ?? 0),
            'site_id' => (int) ($process->site_id ?? 0),
            'process_id' => (int) ($process->id ?? 0),
        ];

        $sourceContext = $this->resolveLinkedSourceContext(
            $validated['source_type'],
            (int) $validated['source_id'],
            $tenantContext['enterprise_id'],
            $tenantContext['site_id'],
            $tenantContext['process_id'],
        );

        if ($sourceContext === null) {
            return response()->json([
                'success' => false,
                'message' => 'Source liée introuvable ou hors périmètre.',
            ], 404);
        }

        $responsibleId = (int) ($validated['responsible_id'] ?? 0);
        if ($responsibleId <= 0) {
            $responsibleId = (int) ($process->pilot_id ?? $user->id ?? 0);
        }
        if ($responsibleId <= 0) {
            $responsibleId = (int) $user->id;
        }

        $review = $this->resolveCurrentReview($process, $user);
        $traceability = [
            'origin' => 'process_review_section_6',
            'process_review_id' => (int) $review->id,
            'source_type' => $validated['source_type'],
            'source_id' => (int) $validated['source_id'],
            'created_by' => (int) $user->id,
            'created_at' => now()->toIso8601String(),
        ];

        $action = Action::query()->create([
            'site_id' => $tenantContext['site_id'],
            'enterprise_id' => $tenantContext['enterprise_id'],
            'process_id' => (int) ($sourceContext['process_id'] ?: $tenantContext['process_id']),
            'source_type' => $validated['source_type'],
            'source_id' => (int) $validated['source_id'],
            'source' => 'management_review',
            'type' => $validated['type'] ?? 'corrective',
            'priority' => $validated['priority'] ?? 'medium',
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')) ?: 'Action créée depuis la Revue Processus (Section 6).',
            'initiator_id' => (int) $user->id,
            'responsible_id' => $responsibleId,
            'deadline' => $validated['deadline'],
            'status' => 'planned',
            'm7_d4_traceability' => $traceability,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($action)
            ->withProperties([
                'process_id' => $process->id,
                'source_type' => $validated['source_type'],
                'source_id' => (int) $validated['source_id'],
                'origin' => 'process_review_section_6',
            ])
            ->log('process_review.linked_action_created');

        return response()->json([
            'success' => true,
            'message' => 'Action liée créée.',
            'data' => [
                'id' => (int) $action->id,
                'ref' => (string) $action->ref,
                'title' => (string) $action->title,
                'source_type' => (string) $action->source_type,
                'source_id' => (int) $action->source_id,
                'deadline' => optional($action->deadline)->format('Y-m-d'),
                'responsible_id' => (int) $action->responsible_id,
            ],
        ], 201);
    }

    private function ensureAccess(Process $process, User $user, string $action = 'read'): void
    {
        if ($action === 'update') {
            if ($this->canUpdateReview($process, $user)) {
                return;
            }

            abort(403, 'Vous n’êtes pas autorisé à modifier cette revue processus.');
        }

        if ($this->canReadReview($process, $user)) {
            return;
        }

        abort(403, 'Vous n’êtes pas autorisé à consulter cette revue processus.');
    }

    private function buildLinkedData(Process $process): array
    {
        $enterpriseId = (int) ($process->enterprise_id ?? 0);
        $siteId = (int) ($process->site_id ?? 0);
        $processId = (int) ($process->id ?? 0);

        $result = [
            'duerp_top' => [],
            'aes_top' => [],
            'incidents_top' => [],
            'summary' => [
                'duerp_overdue_actions' => 0,
                'aes_missing_actions' => 0,
                'incidents_open_count' => 0,
                'incidents_overdue_count' => 0,
                'linked_incident_actions_overdue_count' => 0,
                'alerts' => [],
            ],
        ];

        try {
            if (
                Schema::hasTable('duerp')
                && Schema::hasTable('duerp_dangers')
                && Schema::hasTable('processes')
                && Schema::hasColumn('duerp_dangers', 'duerp_id')
            ) {
                $duerpIdsQuery = DB::table('duerp')
                    ->whereNull('deleted_at');
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('duerp', 'site_id')) {
                    $duerpIdsQuery->where('site_id', $siteId);
                    $hasTenantScope = true;
                }
                if ($enterpriseId > 0 && Schema::hasColumn('duerp', 'enterprise_id')) {
                    $duerpIdsQuery->where('enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    $dangerQuery = DB::table('duerp_dangers')
                        ->leftJoin('processes', 'processes.id', '=', 'duerp_dangers.process_id')
                        ->whereNull('duerp_dangers.deleted_at')
                        ->whereIn('duerp_dangers.duerp_id', $duerpIdsQuery);

                    if ($processId > 0 && Schema::hasColumn('duerp_dangers', 'process_id')) {
                        $dangerQuery->where(function ($q) use ($processId): void {
                            $q->where('duerp_dangers.process_id', $processId)
                                ->orWhereNull('duerp_dangers.process_id');
                        });
                    }

                    $dangerRows = $dangerQuery
                        ->orderByRaw('COALESCE(duerp_dangers.criticality_score, 0) DESC')
                        ->orderByDesc('duerp_dangers.id')
                        ->limit(3)
                        ->get([
                            'duerp_dangers.id',
                            'duerp_dangers.process_id',
                            'duerp_dangers.danger_type',
                            'duerp_dangers.danger_description',
                            'duerp_dangers.criticality_score',
                            'duerp_dangers.criticality_level',
                            'duerp_dangers.actions',
                            DB::raw('COALESCE(processes.title, processes.name) as process_title'),
                        ]);

                    $duerpOverdueActions = 0;
                    $result['duerp_top'] = $dangerRows->map(function ($row) use (&$duerpOverdueActions, $enterpriseId, $siteId) {
                        $overdueActions = $this->countOverdueActions(data_get($row, 'actions'));
                        $duerpOverdueActions += $overdueActions;
                        $sourceId = (int) data_get($row, 'id');

                        return [
                            'id' => $sourceId,
                            'process_id' => data_get($row, 'process_id') !== null ? (int) data_get($row, 'process_id') : null,
                            'process_title' => (string) (data_get($row, 'process_title') ?: ''),
                            'danger_type' => (string) (data_get($row, 'danger_type') ?: ''),
                            'danger_description' => (string) (data_get($row, 'danger_description') ?: ''),
                            'criticality_score' => (int) (data_get($row, 'criticality_score') ?: 0),
                            'criticality_level' => (string) (data_get($row, 'criticality_level') ?: ''),
                            'overdue_actions' => $overdueActions,
                            'linked_actions_count' => $this->countLinkedActionsForSource(
                                'duerp_danger',
                                $sourceId,
                                $enterpriseId,
                                $siteId,
                            ),
                        ];
                    })->values()->all();

                    $result['summary']['duerp_overdue_actions'] = $duerpOverdueActions;
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('aspects_environnementaux') && Schema::hasTable('processes')) {
                $aspectQuery = DB::table('aspects_environnementaux')
                    ->leftJoin('processes', 'processes.id', '=', 'aspects_environnementaux.process_id')
                    ->whereNull('aspects_environnementaux.deleted_at');
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('aspects_environnementaux', 'site_id')) {
                    $aspectQuery->where('aspects_environnementaux.site_id', $siteId);
                    $hasTenantScope = true;
                }
                if ($enterpriseId > 0 && Schema::hasColumn('aspects_environnementaux', 'enterprise_id')) {
                    $aspectQuery->where('aspects_environnementaux.enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    if ($processId > 0 && Schema::hasColumn('aspects_environnementaux', 'process_id')) {
                        $aspectQuery->where(function ($q) use ($processId): void {
                            $q->where('aspects_environnementaux.process_id', $processId)
                                ->orWhereNull('aspects_environnementaux.process_id');
                        });
                    }

                    if (Schema::hasColumn('aspects_environnementaux', 'aspect_significatif')) {
                        $aspectQuery->where('aspects_environnementaux.aspect_significatif', true);
                    }

                    $aspectRows = $aspectQuery
                        ->orderByRaw('COALESCE(aspects_environnementaux.criticite, 0) DESC')
                        ->orderByDesc('aspects_environnementaux.id')
                        ->limit(3)
                        ->get([
                            'aspects_environnementaux.id',
                            'aspects_environnementaux.process_id',
                            'aspects_environnementaux.designation',
                            'aspects_environnementaux.type',
                            'aspects_environnementaux.criticite',
                            'aspects_environnementaux.aspect_significatif',
                            'aspects_environnementaux.objectifs_amelioration',
                            DB::raw('COALESCE(processes.title, processes.name) as process_title'),
                        ]);

                    $aesMissingActions = 0;
                    $result['aes_top'] = $aspectRows->map(function ($row) use (&$aesMissingActions, $enterpriseId, $siteId) {
                        $actionStatus = $this->resolveAesActionStatus(data_get($row, 'objectifs_amelioration'));
                        if ($actionStatus === 'missing') {
                            $aesMissingActions++;
                        }
                        $sourceId = (int) data_get($row, 'id');

                        return [
                            'id' => $sourceId,
                            'process_id' => data_get($row, 'process_id') !== null ? (int) data_get($row, 'process_id') : null,
                            'process_title' => (string) (data_get($row, 'process_title') ?: ''),
                            'designation' => (string) (data_get($row, 'designation') ?: ''),
                            'type' => (string) (data_get($row, 'type') ?: ''),
                            'criticite' => (int) (data_get($row, 'criticite') ?: 0),
                            'aspect_significatif' => (bool) data_get($row, 'aspect_significatif'),
                            'action_status' => $actionStatus,
                            'linked_actions_count' => $this->countLinkedActionsForSource(
                                'aes_aspect',
                                $sourceId,
                                $enterpriseId,
                                $siteId,
                            ),
                        ];
                    })->values()->all();

                    $result['summary']['aes_missing_actions'] = $aesMissingActions;
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('reclamations')) {
                $incidentQuery = DB::table('reclamations')
                    ->whereNull('reclamations.deleted_at');
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('reclamations', 'site_id')) {
                    $incidentQuery->where('reclamations.site_id', $siteId);
                    $hasTenantScope = true;
                }

                if ($enterpriseId > 0 && Schema::hasTable('sites') && Schema::hasColumn('reclamations', 'site_id')) {
                    $incidentQuery->join('sites', 'sites.id', '=', 'reclamations.site_id');
                    $incidentQuery->where('sites.enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    $incidentRows = $incidentQuery
                        ->orderByDesc('reclamations.id')
                        ->limit(3)
                        ->get([
                            'reclamations.id',
                            'reclamations.title',
                            'reclamations.category',
                            'reclamations.severity',
                            'reclamations.status',
                            'reclamations.due_date',
                            'reclamations.description',
                        ]);

                    $openCount = 0;
                    $overdueCount = 0;
                    $result['incidents_top'] = $incidentRows->map(function ($row) use (&$openCount, &$overdueCount, $enterpriseId, $siteId) {
                        $status = mb_strtolower((string) (data_get($row, 'status') ?: ''));
                        $isClosed = in_array($status, ['closed', 'fermee', 'fermée', 'resolue', 'résolue', 'resolved'], true);
                        if (!$isClosed) {
                            $openCount++;
                        }

                        $dueDate = data_get($row, 'due_date');
                        $isOverdue = false;
                        if (!$isClosed && !empty($dueDate)) {
                            try {
                                $isOverdue = now()->greaterThan(\Illuminate\Support\Carbon::parse((string) $dueDate));
                            } catch (\Throwable) {
                                $isOverdue = false;
                            }
                        }
                        if ($isOverdue) {
                            $overdueCount++;
                        }

                        $sourceId = (int) data_get($row, 'id');

                        return [
                            'id' => $sourceId,
                            'title' => (string) (data_get($row, 'title') ?: ''),
                            'category' => (string) (data_get($row, 'category') ?: ''),
                            'severity' => (string) (data_get($row, 'severity') ?: ''),
                            'status' => (string) (data_get($row, 'status') ?: ''),
                            'due_date' => data_get($row, 'due_date'),
                            'description' => (string) (data_get($row, 'description') ?: ''),
                            'is_overdue' => $isOverdue,
                            'linked_actions_count' => $this->countLinkedActionsForSource(
                                'reclamation',
                                $sourceId,
                                $enterpriseId,
                                $siteId,
                            ),
                        ];
                    })->values()->all();

                    $result['summary']['incidents_open_count'] = $openCount;
                    $result['summary']['incidents_overdue_count'] = $overdueCount;
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('actions')) {
                $actionsQuery = DB::table('actions')
                    ->whereNull('deleted_at')
                    ->where('source_type', 'reclamation');

                if ($siteId > 0 && Schema::hasColumn('actions', 'site_id')) {
                    $actionsQuery->where('site_id', $siteId);
                }
                if ($enterpriseId > 0 && Schema::hasColumn('actions', 'enterprise_id')) {
                    $actionsQuery->where('enterprise_id', $enterpriseId);
                }
                if ($processId > 0 && Schema::hasColumn('actions', 'process_id')) {
                    $actionsQuery->where('process_id', $processId);
                }

                if (Schema::hasColumn('actions', 'deadline') && Schema::hasColumn('actions', 'status')) {
                    $result['summary']['linked_incident_actions_overdue_count'] = (int) (clone $actionsQuery)
                        ->whereNotNull('deadline')
                        ->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])
                        ->where('deadline', '<', now()->toDateString())
                        ->count();
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        $alerts = [];
        if ((int) ($result['summary']['incidents_open_count'] ?? 0) > 0) {
            $alerts[] = [
                'code' => 'incidents_open',
                'severity' => 'warning',
                'message' => sprintf(
                    '%d incident(s) non clôturé(s) à traiter.',
                    (int) $result['summary']['incidents_open_count']
                ),
            ];
        }
        if ((int) ($result['summary']['incidents_overdue_count'] ?? 0) > 0) {
            $alerts[] = [
                'code' => 'incidents_overdue',
                'severity' => 'error',
                'message' => sprintf(
                    '%d incident(s) avec échéance dépassée.',
                    (int) $result['summary']['incidents_overdue_count']
                ),
            ];
        }
        if ((int) ($result['summary']['linked_incident_actions_overdue_count'] ?? 0) > 0) {
            $alerts[] = [
                'code' => 'incident_actions_overdue',
                'severity' => 'error',
                'message' => sprintf(
                    '%d action(s) incidents échue(s).',
                    (int) $result['summary']['linked_incident_actions_overdue_count']
                ),
            ];
        }
        $result['summary']['alerts'] = $alerts;

        return $result;
    }

    private function countOverdueActions(mixed $rawActions): int
    {
        $actions = $rawActions;

        // Tolère les formats json/jsonb parfois renvoyés double-encodés.
        for ($i = 0; $i < 3; $i++) {
            if (is_string($actions)) {
                $decoded = json_decode($actions, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    break;
                }
                $actions = $decoded;
                continue;
            }

            if (is_object($actions)) {
                $actions = json_decode(json_encode($actions), true);
                continue;
            }

            break;
        }

        if (!is_array($actions)) {
            return 0;
        }

        $count = 0;
        foreach ($actions as $action) {
            if (is_object($action)) {
                $action = json_decode(json_encode($action), true);
            }
            if (!is_array($action)) {
                continue;
            }

            $status = mb_strtolower((string) ($action['status'] ?? ''));
            $isDone = in_array($status, ['done', 'completed', 'closed', 'termine', 'terminé'], true);

            $deadlineRaw = $action['deadline'] ?? $action['due_date'] ?? $action['date_echeance'] ?? null;
            if ($isDone || empty($deadlineRaw)) {
                continue;
            }

            try {
                if (now()->greaterThan(\Illuminate\Support\Carbon::parse((string) $deadlineRaw))) {
                    $count++;
                }
            } catch (\Throwable) {
                // ignore invalid action date
            }
        }

        return $count;
    }

    private function resolveAesActionStatus(mixed $objectifsAmelioration): string
    {
        $value = trim((string) ($objectifsAmelioration ?? ''));
        return $value === '' ? 'missing' : 'defined';
    }

    private function countLinkedActionsForSource(string $sourceType, int $sourceId, int $enterpriseId, int $siteId): int
    {
        try {
            if (!Schema::hasTable('actions') || $sourceId <= 0) {
                return 0;
            }

            $query = DB::table('actions')
                ->whereNull('deleted_at')
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId);

            if ($enterpriseId > 0 && Schema::hasColumn('actions', 'enterprise_id')) {
                $query->where('enterprise_id', $enterpriseId);
            }
            if ($siteId > 0 && Schema::hasColumn('actions', 'site_id')) {
                $query->where('site_id', $siteId);
            }

            return (int) $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function resolveLinkedSourceContext(
        string $sourceType,
        int $sourceId,
        int $enterpriseId,
        int $siteId,
        int $currentProcessId,
    ): ?array {
        if ($sourceId <= 0) {
            return null;
        }

        if ($sourceType === 'duerp_danger') {
            if (
                !Schema::hasTable('duerp')
                || !Schema::hasTable('duerp_dangers')
                || !Schema::hasColumn('duerp_dangers', 'duerp_id')
            ) {
                return null;
            }

            $query = DB::table('duerp_dangers')
                ->join('duerp', 'duerp.id', '=', 'duerp_dangers.duerp_id')
                ->whereNull('duerp_dangers.deleted_at')
                ->whereNull('duerp.deleted_at')
                ->where('duerp_dangers.id', $sourceId);

            if ($enterpriseId > 0 && Schema::hasColumn('duerp', 'enterprise_id')) {
                $query->where('duerp.enterprise_id', $enterpriseId);
            }
            if ($siteId > 0 && Schema::hasColumn('duerp', 'site_id')) {
                $query->where('duerp.site_id', $siteId);
            }
            if ($currentProcessId > 0 && Schema::hasColumn('duerp_dangers', 'process_id')) {
                $query->where(function ($q) use ($currentProcessId): void {
                    $q->where('duerp_dangers.process_id', $currentProcessId)
                        ->orWhereNull('duerp_dangers.process_id');
                });
            }

            $row = $query->first(['duerp_dangers.id', 'duerp_dangers.process_id']);
            if (!$row) {
                return null;
            }

            return [
                'source_type' => $sourceType,
                'source_id' => (int) data_get($row, 'id'),
                'process_id' => data_get($row, 'process_id') !== null ? (int) data_get($row, 'process_id') : null,
            ];
        }

        if ($sourceType === 'aes_aspect') {
            if (!Schema::hasTable('aspects_environnementaux')) {
                return null;
            }

            $query = DB::table('aspects_environnementaux')
                ->whereNull('deleted_at')
                ->where('id', $sourceId);

            if ($enterpriseId > 0 && Schema::hasColumn('aspects_environnementaux', 'enterprise_id')) {
                $query->where('enterprise_id', $enterpriseId);
            }
            if ($siteId > 0 && Schema::hasColumn('aspects_environnementaux', 'site_id')) {
                $query->where('site_id', $siteId);
            }
            if ($currentProcessId > 0 && Schema::hasColumn('aspects_environnementaux', 'process_id')) {
                $query->where(function ($q) use ($currentProcessId): void {
                    $q->where('process_id', $currentProcessId)
                        ->orWhereNull('process_id');
                });
            }

            $row = $query->first(['id', 'process_id']);
            if (!$row) {
                return null;
            }

            return [
                'source_type' => $sourceType,
                'source_id' => (int) data_get($row, 'id'),
                'process_id' => data_get($row, 'process_id') !== null ? (int) data_get($row, 'process_id') : null,
            ];
        }

        if ($sourceType === 'reclamation') {
            if (!Schema::hasTable('reclamations')) {
                return null;
            }

            $query = DB::table('reclamations')
                ->whereNull('reclamations.deleted_at')
                ->where('reclamations.id', $sourceId);

            if ($siteId > 0 && Schema::hasColumn('reclamations', 'site_id')) {
                $query->where('reclamations.site_id', $siteId);
            }

            if ($enterpriseId > 0 && Schema::hasTable('sites') && Schema::hasColumn('reclamations', 'site_id')) {
                $query->join('sites', 'sites.id', '=', 'reclamations.site_id');
                $query->where('sites.enterprise_id', $enterpriseId);
            }

            $row = $query->first(['reclamations.id']);
            if (!$row) {
                return null;
            }

            return [
                'source_type' => $sourceType,
                'source_id' => (int) data_get($row, 'id'),
                'process_id' => null,
            ];
        }

        return null;
    }

    private function canEditIdentification(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $roles = $user->roles->pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();
        $legacyRole = mb_strtolower((string) ($user->role ?? ''));

        $hasRqRole = collect($roles)
            ->contains(fn (string $roleName) => str_contains($roleName, 'quality') || str_contains($roleName, 'qualit') || str_contains($roleName, 'rq'))
            || str_contains($legacyRole, 'quality')
            || str_contains($legacyRole, 'qualit')
            || str_contains($legacyRole, 'rq');

        if ($hasRqRole) {
            return true;
        }

        $permissionNames = $user->getAllPermissions()->pluck('name')->all();
        foreach ($permissionNames as $permissionName) {
            $name = mb_strtolower((string) $permissionName);
            if (str_contains($name, 'revue_processus') && (str_ends_with($name, '.manage') || str_ends_with($name, '.update'))) {
                return true;
            }
        }

        return false;
    }

    private function resolveCurrentReview(Process $process, User $user): ProcessReview
    {
        $openReview = ProcessReview::query()
            ->where('process_id', $process->id)
            ->whereIn('status', [ProcessReview::STATUS_PLANNED, ProcessReview::STATUS_IN_PROGRESS])
            ->latest('id')
            ->first();

        if ($openReview) {
            return $openReview;
        }

        $latestReview = ProcessReview::query()
            ->where('process_id', $process->id)
            ->latest('id')
            ->first();

        if ($latestReview && $latestReview->status === ProcessReview::STATUS_COMPLETED) {
            return $latestReview;
        }

        return ProcessReview::query()->create([
            'process_id' => $process->id,
            'site_id' => $process->site_id,
            'type' => 'periodique',
            'review_date' => now()->toDateString(),
            'version_reviewed' => (string) ($process->version ?? '1.0'),
            'led_by' => $user->id,
            'status' => ProcessReview::STATUS_PLANNED,
            'identification' => [
                'rq_name' => $process->pilot?->name ?: $user->name,
                'include_pilot' => true,
                'include_copilot' => false,
                'present_user_ids' => [],
                'present_others' => '',
                'coverage_start' => now()->toDateString(),
                'coverage_end' => now()->toDateString(),
                'started_at' => now()->format('d/m/Y H:i'),
                'ended_at' => null,
            ],
            'sections' => [
                'pip_summary' => '',
                'risk_opportunity_summary' => '',
                'objectives_projects_summary' => '',
                'compliance_nc_satisfaction_summary' => '',
                'management_duerp_display' => '',
            ],
            'metrics_snapshot' => [],
            'participants' => [],
        ]);
    }

    private function syncGeneratedReportToInventory(Process $process, User $user, string $path, string $extension): ?\App\Models\Document
    {
        return $this->inventorySyncService->syncGeneratedProcessDocument([
            'site_id' => $process->site_id,
            'process_id' => $process->id,
            'process_code' => $process->code,
            'process_name' => $process->title,
            'document_kind' => 'process_review_report',
            'title' => 'Rapport Revue Processus - ' . ($process->title ?? $process->code),
            'description' => 'Rapport généré depuis le sous-module Revue Processus',
            'file_source_path' => $path,
            'file_extension' => $extension,
            'created_by' => $user->id,
            'type' => 'ENR',
            'force_new' => true,
        ]);
    }

    private function isAssignedToProcess(Process $process, User $user): bool
    {
        $userId = (int) $user->id;

        return in_array($userId, [
            (int) ($process->pilot_id ?? 0),
            (int) ($process->copilot_id ?? 0),
            (int) ($process->process_owner_id ?? 0),
        ], true);
    }

    private function isReviewParticipant(Process $process, User $user): bool
    {
        return ProcessReview::query()
            ->where('process_id', $process->id)
            ->whereJsonContains('participants', (int) $user->id)
            ->exists();
    }

    private function isQualityUser(User $user): bool
    {
        $roleNames = $user->roles->pluck('name')
            ->map(fn ($name) => mb_strtolower((string) $name))
            ->all();

        $legacyRole = mb_strtolower((string) ($user->role ?? ''));
        $isRoleBasedQuality = collect($roleNames)->contains(fn (string $role) =>
            str_contains($role, 'quality')
            || str_contains($role, 'qualit')
            || str_contains($role, 'rq')
        );

        return $isRoleBasedQuality
            || str_contains($legacyRole, 'quality')
            || str_contains($legacyRole, 'qualit')
            || str_contains($legacyRole, 'rq');
    }

    private function computeMetrics(Process $process): array
    {
        $enterpriseId = (int) ($process->enterprise_id ?? 0);
        $siteId = (int) ($process->site_id ?? 0);

        $metrics = [
            'pip_total' => 0,
            'pip_completed' => 0,
            'risks_count' => 0,
            'opportunities_count' => 0,
            'objectives_count' => 0,
            'objective_rate' => 0,
            'non_conformities_count' => 0,
            'satisfaction_rate' => 0,
            'duerp_versions_count' => 0,
            'duerp_dangers_count' => 0,
            'duerp_unacceptable_count' => 0,
            'aes_total_count' => 0,
            'aes_significant_count' => 0,
        ];

        try {
            $risksOpportunities = $process->risksOpportunities()->get(['type']);
            $metrics['risks_count'] = $risksOpportunities->where('type', 'risque')->count();
            $metrics['opportunities_count'] = $risksOpportunities->where('type', 'opportunite')->count();
        } catch (\Throwable) {
            // best effort
        }

        try {
            $objectiveItems = collect();
            if (method_exists($process, 'processObjectives')) {
                $objectiveItems = $process->processObjectives()->get();
            }
            if ($objectiveItems->isEmpty() && method_exists($process, 'objectives')) {
                $objectiveItems = $process->objectives()->get();
            }

            $metrics['objectives_count'] = $objectiveItems->count();
            if ($objectiveItems->isNotEmpty()) {
                $rates = $objectiveItems
                    ->map(function ($item) {
                        $value = data_get($item, 'achievement_percentage');
                        if ($value !== null) {
                            return (float) $value;
                        }

                        $current = (float) data_get($item, 'current_value', 0);
                        $target = (float) data_get($item, 'target_value', 0);
                        if ($target > 0) {
                            return min(100, max(0, round(($current / $target) * 100)));
                        }

                        return 0.0;
                    })
                    ->values();
                $metrics['objective_rate'] = (int) round($rates->avg() ?? 0);
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (method_exists($process, 'nonConformities')) {
                $metrics['non_conformities_count'] = $process->nonConformities()->count();
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (Schema::hasTable('evaluation_requests')) {
                $query = \App\Models\EvaluationRequest::query();
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('evaluation_requests', 'site_id')) {
                    $query->where('site_id', $siteId);
                    $hasTenantScope = true;
                }

                if ($enterpriseId > 0 && Schema::hasColumn('evaluation_requests', 'enterprise_id')) {
                    $query->where('enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    $metrics['pip_total'] = (int) (clone $query)->count();
                    $metrics['pip_completed'] = (int) (clone $query)->where('status', 'completed')->count();
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (
                Schema::hasTable('evaluation_responses')
                && Schema::hasTable('evaluation_requests')
                && Schema::hasColumn('evaluation_responses', 'percentage')
            ) {
                $canScopeBySite = $siteId > 0 && Schema::hasColumn('evaluation_requests', 'site_id');
                $canScopeByEnterprise = $enterpriseId > 0 && Schema::hasColumn('evaluation_requests', 'enterprise_id');

                if ($canScopeBySite || $canScopeByEnterprise) {
                    $avg = \App\Models\EvaluationResponse::query()
                        ->whereHas('request', function ($q) use ($siteId, $enterpriseId, $canScopeBySite, $canScopeByEnterprise): void {
                            if ($canScopeBySite) {
                                $q->where('site_id', $siteId);
                            }
                            if ($canScopeByEnterprise) {
                                $q->where('enterprise_id', $enterpriseId);
                            }
                        })
                        ->avg('percentage');
                    $metrics['satisfaction_rate'] = (int) round((float) ($avg ?? 0));
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (
                Schema::hasTable('duerp')
                && Schema::hasTable('duerp_dangers')
                && Schema::hasColumn('duerp_dangers', 'duerp_id')
            ) {
                $duerpIdsQuery = DB::table('duerp')
                    ->whereNull('deleted_at')
                    ->select('id');
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('duerp', 'site_id')) {
                    $duerpIdsQuery->where('site_id', $siteId);
                    $hasTenantScope = true;
                }

                if ($enterpriseId > 0 && Schema::hasColumn('duerp', 'enterprise_id')) {
                    $duerpIdsQuery->where('enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    $metrics['duerp_versions_count'] = (int) (clone $duerpIdsQuery)->count();

                    $metrics['duerp_dangers_count'] = (int) DB::table('duerp_dangers')
                        ->whereNull('duerp_dangers.deleted_at')
                        ->whereIn('duerp_dangers.duerp_id', $duerpIdsQuery)
                        ->count();

                    $metrics['duerp_unacceptable_count'] = (int) DB::table('duerp_dangers')
                        ->whereNull('duerp_dangers.deleted_at')
                        ->whereIn('duerp_dangers.duerp_id', $duerpIdsQuery)
                        ->where('duerp_dangers.criticality_level', 'unacceptable')
                        ->count();
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        try {
            if (
                Schema::hasTable('aspects_environnementaux')
            ) {
                $aspectQuery = DB::table('aspects_environnementaux')
                    ->whereNull('deleted_at');
                $hasTenantScope = false;

                if ($siteId > 0 && Schema::hasColumn('aspects_environnementaux', 'site_id')) {
                    $aspectQuery->where('site_id', $siteId);
                    $hasTenantScope = true;
                }

                if ($enterpriseId > 0 && Schema::hasColumn('aspects_environnementaux', 'enterprise_id')) {
                    $aspectQuery->where('enterprise_id', $enterpriseId);
                    $hasTenantScope = true;
                }

                if ($hasTenantScope) {
                    $metrics['aes_total_count'] = (int) (clone $aspectQuery)->count();

                    if (Schema::hasColumn('aspects_environnementaux', 'aspect_significatif')) {
                        $metrics['aes_significant_count'] = (int) (clone $aspectQuery)
                            ->where('aspect_significatif', true)
                            ->count();
                    } elseif (
                        Schema::hasColumn('aspects_environnementaux', 'gravite')
                        && Schema::hasColumn('aspects_environnementaux', 'frequence')
                        && Schema::hasColumn('aspects_environnementaux', 'detectabilite')
                    ) {
                        $metrics['aes_significant_count'] = (int) (clone $aspectQuery)
                            ->whereRaw('(gravite * frequence * detectabilite) >= 50')
                            ->count();
                    }
                }
            }
        } catch (\Throwable) {
            // best effort
        }

        return $metrics;
    }

    private function buildCapabilities(Process $process, User $user): array
    {
        return [
            'can_read_review' => $this->canReadReview($process, $user),
            'can_update_review' => $this->canUpdateReview($process, $user),
            'can_edit_identification' => $this->canUpdateReview($process, $user) && $this->canEditIdentification($user),
        ];
    }

    private function canReadReview(Process $process, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) ($process->enterprise_id ?? 0) !== (int) ($user->enterprise_id ?? 0)) {
            return false;
        }

        if ($user->isEnterpriseAdmin() || $user->isSiteManager()) {
            return true;
        }

        $isAssigned = $this->isAssignedToProcess($process, $user);
        $isQuality = $this->isQualityUser($user);
        $isParticipant = $this->isReviewParticipant($process, $user);

        return $isAssigned || $isQuality || $isParticipant;
    }

    private function canUpdateReview(Process $process, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) ($process->enterprise_id ?? 0) !== (int) ($user->enterprise_id ?? 0)) {
            return false;
        }

        if ($user->isEnterpriseAdmin() || $user->isSiteManager()) {
            return true;
        }

        $isAssigned = $this->isAssignedToProcess($process, $user);
        $isQuality = $this->isQualityUser($user);

        return ($isAssigned || $isQuality) && $user->can('process_reviews.update');
    }

    /**
     * GET /api/v1/processes/{id}/metrics
     * Expose process metrics for dashboard visualization
     */
    public function getMetrics(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $process = Process::findOrFail($id);

        // Authorization check
        if (!$this->canViewProcess($process, $user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get metrics using private method
        $metrics = $this->computeMetrics($process);

        return response()->json([
            'success' => true,
            'data' => [
                'process_id' => $process->id,
                'process_name' => $process->name,
                'metrics' => $metrics,
            ],
        ]);
    }
}
