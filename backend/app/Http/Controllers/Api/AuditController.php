<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddAuditFindingRequest;
use App\Http\Requests\StoreAuditRequest;
use App\Http\Requests\UpdateAuditRequest;
use App\Models\Audit;
use App\Models\AuditProgram;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationRequest;
use App\Services\AuditService;
use App\Services\Core\DynamicFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
    public function __construct(
        protected AuditService $service,
        protected DynamicFieldService $dynamicFieldService
    ) {}

    private function scopedAuditQuery()
    {
        $query = Audit::query();
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if (!$user->enterprise_id) {
                $query->whereRaw('1 = 0');
                return $query;
            }

            // Compatibilité schéma: certaines bases n'ont pas audits.enterprise_id.
            if (Schema::hasColumn('audits', 'enterprise_id')) {
                $query->withoutGlobalScope('enterprise')
                    ->where('enterprise_id', $user->enterprise_id);
            } else {
                $query->withoutGlobalScope('enterprise')
                    ->whereHas('site', function ($siteQuery) use ($user) {
                        $siteQuery->where('enterprise_id', $user->enterprise_id);
                    });
            }
        }

        return $query;
    }

    private function findScopedAuditOrFail(int $id, array $with = []): Audit
    {
        $query = $this->scopedAuditQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $query = $this->scopedAuditQuery()
            ->with([
                'site',
                'leadAuditor',
                'assignedTo',
                'axes',
                'workflowState',
                'nonConformities',
                'processes',
                'program.site',
                'program.programManager',
            ]);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('year')) {
            $query->whereYear('planned_date', $request->year);
        }

        $audits = $query->latest('planned_date')->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    public function store(StoreAuditRequest $request): JsonResponse
    {
        $this->authorize('create', Audit::class);

        $validated = $request->validated();
        $isInternalFlow = !in_array(($validated['type'] ?? null), ['external', 'certification'], true);

        if ($isInternalFlow) {
            $programId = (int) ($validated['audit_program_id'] ?? 0);
            $program = null;

            if ($programId > 0) {
                $program = AuditProgram::query()->find($programId);
                if (!$program) {
                    return response()->json([
                        'message' => "Le programme d'audit sélectionné est introuvable.",
                    ], 422);
                }
            } else {
                $plannedYear = (int) date('Y', strtotime((string) ($validated['planned_date'] ?? '')));
                $program = $this->findOrCreateProgramForAudit($validated, $plannedYear);
                $validated['audit_program_id'] = $program->id;
            }

            if ((int) $program->site_id !== (int) ($validated['site_id'] ?? 0)) {
                return response()->json([
                    'message' => "Le programme d'audit doit appartenir au même site que le plan d'audit.",
                ], 422);
            }

            $plannedYear = (int) date('Y', strtotime((string) ($validated['planned_date'] ?? '')));
            if ((int) $program->year !== $plannedYear) {
                return response()->json([
                    'message' => "L'année du programme d'audit doit correspondre à l'année de planification de l'audit.",
                ], 422);
            }
        }

        $audit = $this->service->create($validated);

        return response()->json($audit, 201);
    }

    private function findOrCreateProgramForAudit(array $validated, int $plannedYear): AuditProgram
    {
        $siteId = (int) ($validated['site_id'] ?? 0);
        $managerId = (int) ($validated['lead_auditor_id'] ?? auth()->id());

        return DB::transaction(function () use ($siteId, $plannedYear, $managerId) {
            return AuditProgram::query()->firstOrCreate(
                [
                    'site_id' => $siteId,
                    'year' => $plannedYear,
                ],
                [
                    'program_manager_id' => $managerId,
                    'status' => 'draft',
                    'planned_audits_count' => 0,
                    'title' => "Programme Audits Internes {$plannedYear}",
                ]
            );
        });
    }

    public function show(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id, [
            'site', 'leadAuditor', 'assignedTo', 'axes', 'workflowState',
            'nonConformities', 'actions', 'processes',
        ]);

        $this->authorize('view', $audit);

        return response()->json($audit);
    }

    public function update(UpdateAuditRequest $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $audit->update($request->validated());

        return response()->json($this->loadAuditWithRelations($audit->id));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $audit = Audit::findOrFail($id);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'planned',
                    'in_progress',
                    'report_draft',
                    'report_approved',
                    'completed',
                    'closed',
                ]),
            ],
        ]);

        $audit->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Statut de l’audit mis à jour avec succès.',
            'data' => $this->loadAuditWithRelations($audit->id),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('delete', $audit);

        // Ne pas supprimer les audits complétés
        if ($audit->status === 'completed') {
            return response()->json([
                'message' => 'Impossible de supprimer un audit terminé',
            ], 422);
        }

        $audit->delete();

        return response()->json(['message' => 'Audit supprimé'], 200);
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('start', $audit);

        $audit = $this->service->start($audit, $request->all());

        return response()->json($audit);
    }

    public function addChecklist(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('addChecklist', $audit);

        $audit = $this->service->addChecklist($audit, $request->input('checklist', []));

        return response()->json($audit);
    }

    public function addFinding(AddAuditFindingRequest $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('addFinding', $audit);

        $finding = $this->service->addFinding($audit, $request->validated());

        return response()->json($finding, 201);
    }

    public function findings(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('view', $audit);

        $findings = $audit->findings()->latest()->get();

        return response()->json([
            'data' => $findings,
        ]);
    }

    public function auditorEvaluationCriteria(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('view', $audit);

        $user = auth()->user();
        $enterpriseId = (int) ($user->enterprise_id ?? 0);

        $criteria = EvaluationCriteria::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('form_type', 'evaluation_auditeur')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        if ($criteria->isEmpty()) {
            return response()->json([
                'data' => EvaluationCriteria::getDefaultAuditorEvaluationCriteria(),
                'source' => 'default',
            ]);
        }

        return response()->json([
            'data' => $criteria,
            'source' => 'custom',
        ]);
    }

    public function createAuditorEvaluationRequest(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id, ['site']);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'auditor_user_id' => 'required|integer|exists:users,id',
            'criteria_ids' => 'nullable|array|min:1',
            'criteria_ids.*' => 'integer|exists:evaluation_criteria,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'send_immediately' => 'nullable|boolean',
        ]);

        $user = auth()->user();

        /** @var \App\Models\User $auditor */
        $auditor = \App\Models\User::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->findOrFail((int) $validated['auditor_user_id']);

        $criteriaIds = $validated['criteria_ids'] ?? null;
        if (is_array($criteriaIds)) {
            $validCount = EvaluationCriteria::query()
                ->where('enterprise_id', $user->enterprise_id)
                ->where('form_type', 'evaluation_auditeur')
                ->whereIn('id', $criteriaIds)
                ->count();
            if ($validCount !== count($criteriaIds)) {
                return response()->json([
                    'message' => "Les critères sélectionnés sont invalides pour l'évaluation auditeur.",
                ], 422);
            }
        }

        $evaluationRequest = EvaluationRequest::create([
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $audit->site_id,
            'type' => 'evaluation_auditeur',
            'recipient_email' => $auditor->email,
            'recipient_name' => $auditor->full_name ?? $auditor->name ?? $auditor->username,
            'subject' => $validated['subject'] ?? "Évaluation auditeur - {$audit->ref}",
            'message' => $validated['message'] ?? "Merci de compléter l'évaluation liée à l'audit {$audit->ref}.",
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => !empty($validated['send_immediately']) ? 'sent' : 'draft',
            'sent_at' => !empty($validated['send_immediately']) ? now() : null,
            'requestable_type' => Audit::class,
            'requestable_id' => $audit->id,
            'created_by' => $user->id,
            'metadata' => [
                'audit_ref' => $audit->ref,
                'audit_title' => $audit->title,
                'auditor_user_id' => $auditor->id,
            ],
        ]);

        $this->dynamicFieldService->syncRequestCriteriaSnapshot($evaluationRequest, $criteriaIds);

        return response()->json([
            'message' => "Demande d'évaluation auditeur créée avec succès.",
            'data' => $evaluationRequest->load(['criteria', 'requestable']),
            'public_url' => $evaluationRequest->getPublicUrl(),
        ], 201);
    }

    public function finalize(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('finalize', $audit);

        $this->service->finalize($audit, $request->all());

        return response()->json([
            'message' => 'Audit finalisé, rapport en cours de génération',
        ]);
    }

    /**
     * Backward-compatible endpoint used by some frontend screens.
     * Generates report and moves audit to report_draft.
     */
    public function generateReport(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $format = (string) ($request->input('format', 'pdf'));
        if (!in_array($format, ['pdf', 'docx'], true)) {
            $format = 'pdf';
        }

        $process = $audit->processes()->first();
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $audit->site_id,
            'process_id' => $process?->id,
            'process_code' => $process?->code ?? $process?->abbreviation ?? null,
            'process_name' => $process?->title ?? $process?->name ?? null,
            'title' => sprintf('Rapport d\'audit - %s', $audit->title),
            'description' => sprintf('Rapport généré pour l\'audit %s.', $audit->ref ?? $audit->id),
            'document_kind' => 'audit_report',
            'source_type' => 'audit',
            'source_id' => $audit->id,
            'source_updated_at' => $audit->updated_at?->toISOString(),
            'type' => 'ENR',
            'created_by' => $request->user()?->id,
            'force_new' => true,
            'metadata' => [
                'audit_id' => $audit->id,
                'audit_ref' => $audit->ref,
                'format' => $format,
            ],
        ]);

        dispatch(new \App\Jobs\GenerateAuditReport($audit, $format, $request->user(), $document->id));

        $audit->update([
            'status' => 'report_draft',
            'report_source' => 'auto',
            'report_version' => ((int) ($audit->report_version ?? 0)) + 1,
        ]);

        return response()->json([
            'message' => 'Rapport en cours de génération',
            'generated_document_id' => $document->id,
            'data' => $this->loadAuditWithRelations($audit->id),
        ])->header('X-Generated-Document-Id', (string) $document->id);
    }

    /**
     * Backward-compatible endpoint used by some frontend screens.
     * Marks generated report as approved.
     */
    public function approveReport(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $audit->update([
            'status' => 'report_approved',
        ]);

        return response()->json([
            'message' => 'Rapport approuvé avec succès.',
            'data' => $this->loadAuditWithRelations($audit->id),
        ]);
    }

    public function complete(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('complete', $audit);

        $audit = $this->service->complete($audit);

        return response()->json($audit);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Audit::class);

        $user = auth()->user();
        $filters = $request->only(['site_id', 'axes', 'year']);

        if ($user && !$user->isSuperAdmin()) {
            $filters['enterprise_id'] = $user->enterprise_id;
        }

        $stats = $this->service->getStatistics($filters);

        return response()->json($stats);
    }

    public function generateChecklist(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $checklist = $this->service->generateChecklist(
            $audit,
            $request->input('iso_clauses', []),
            $request->input('include_process_risks', true)
        );

        return response()->json([
            'message' => 'Checklist générée avec succès',
            'checklist' => $checklist,
            'count' => count($checklist),
        ]);
    }

    public function sendInvitations(int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        // Send notifications to auditors and auditees
        $audit->load(['auditors', 'auditees']);

        foreach ($audit->auditors as $auditor) {
            $auditor->notify(new \App\Notifications\AuditInvitation($audit, 'auditor'));
        }

        foreach ($audit->auditees as $auditee) {
            $auditee->notify(new \App\Notifications\AuditInvitation($audit, 'auditee'));
        }

        return response()->json(['message' => 'Invitations envoyées avec succès']);
    }

    public function uploadExternalReport(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'report_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'version' => 'nullable|integer|min:1|max:999',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $validated['report_file'];
        $version = (int) ($validated['version'] ?? (((int) ($audit->report_version ?? 0)) + 1));
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf(
            'audit_%s_external_v%d_%d.%s',
            $audit->ref,
            $version,
            now()->timestamp,
            $extension
        );

        $path = $file->storeAs('audits/reports/external', $filename);

        $audit->update([
            'external_report_path' => $path,
            'report_source' => 'external',
            'report_version' => $version,
            'external_report_uploaded_at' => now(),
        ]);

        activity()
            ->performedOn($audit)
            ->causedBy($request->user())
            ->withProperties(['path' => $path, 'version' => $version])
            ->log('Rapport externe chargé');

        return response()->json([
            'message' => 'Rapport externe chargé avec succès',
            'data' => [
                'external_report_path' => $path,
                'report_source' => 'external',
                'report_version' => $version,
            ],
        ], 201);
    }

    public function downloadReport(Request $request, int $id)
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('downloadReport', $audit);

        $format = $request->query('format', 'pdf');
        $path = $format === 'docx' ? $audit->global_report_path : $audit->report_path;

        if ($request->boolean('external') || (! $path && $audit->report_source === 'external')) {
            $path = $audit->external_report_path;
        }

        if (! $path || ! Storage::exists($path)) {
            return $this->errorResponse(
                'REPORT_NOT_FOUND',
                'Aucun rapport disponible pour cet audit.',
                ['audit_id' => $audit->id],
                404
            );
        }

        return Storage::download($path, basename($path));
    }

    /**
     * Update audit complements (additional info, specific points, etc.)
     */
    public function updateComplements(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'additional_info' => 'nullable|array',
            'input_elements' => 'nullable|array',
            'specific_points' => 'nullable|array',
        ]);

        $audit->update($validated);

        return response()->json($audit->fresh());
    }

    /**
     * Add or update norm inputs for the audit
     */
    public function updateNormInputs(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'norm_inputs' => 'required|array',
            'norm_inputs.*.norm_reference' => 'required|string',
            'norm_inputs.*.clause' => 'required|string',
            'norm_inputs.*.requirement' => 'required|string',
            'norm_inputs.*.evidence_required' => 'nullable|string',
            'norm_inputs.*.is_mandatory' => 'boolean',
        ]);

        // Delete existing and recreate
        $audit->normInputs()->delete();

        foreach ($validated['norm_inputs'] as $input) {
            $audit->normInputs()->create($input);
        }

        return response()->json([
            'message' => 'Éléments d\'entrée mis à jour',
            'norm_inputs' => $audit->normInputs,
        ]);
    }

    /**
     * Generate SM synthesis report for the audit
     */
    public function generateSynthesis(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id, [
            'site',
            'leadAuditor',
            'nonConformities',
            'actions',
            'normInputs',
        ]);

        $this->authorize('view', $audit);

        // Calculate statistics
        $totalNCs = $audit->nonConformities->count();
        $majorNCs = $audit->nonConformities->where('severity', 'major')->count();
        $minorNCs = $audit->nonConformities->where('severity', 'minor')->count();
        $observations = $audit->nonConformities->where('type', 'observation')->count();

        $closedActions = $audit->actions->where('status', 'closed')->count();
        $pendingActions = $audit->actions->where('status', '!=', 'closed')->count();

        // Parse checklist for conformity stats
        $checklist = $audit->checklist ?? [];
        $conformItems = collect($checklist)->where('status', 'conform')->count();
        $nonConformItems = collect($checklist)->where('status', 'non_conform')->count();
        $naItems = collect($checklist)->where('status', 'na')->count();
        $totalItems = count($checklist);
        $applicableItems = $totalItems - $naItems;

        $conformityRate = $applicableItems > 0
            ? round(($conformItems / $applicableItems) * 100, 1)
            : 0;

        $synthesis = [
            'audit_info' => [
                'ref' => $audit->ref,
                'title' => $audit->title,
                'type' => $audit->type,
                'site' => $audit->site?->name,
                'lead_auditor' => $audit->leadAuditor?->name,
                'planned_date' => $audit->planned_date?->format('d/m/Y'),
                'actual_date' => $audit->actual_date?->format('d/m/Y'),
                'status' => $audit->status,
            ],
            'scope' => [
                'scope' => $audit->scope,
                'objectives' => $audit->objectives,
                'input_elements' => $audit->input_elements,
                'specific_points' => $audit->specific_points,
            ],
            'statistics' => [
                'conformity_rate' => $conformityRate,
                'checklist' => [
                    'total' => $totalItems,
                    'conform' => $conformItems,
                    'non_conform' => $nonConformItems,
                    'not_applicable' => $naItems,
                ],
                'non_conformities' => [
                    'total' => $totalNCs,
                    'major' => $majorNCs,
                    'minor' => $minorNCs,
                    'observations' => $observations,
                ],
                'corrective_actions' => [
                    'total' => $closedActions + $pendingActions,
                    'closed' => $closedActions,
                    'pending' => $pendingActions,
                ],
            ],
            'findings' => $audit->nonConformities->map(fn($nc) => [
                'ref' => $nc->ref,
                'type' => $nc->type,
                'severity' => $nc->severity,
                'description' => $nc->description,
                'norm_clause' => $nc->norm_clause,
            ]),
            'conclusions' => $audit->conclusions,
            'recommendations' => $audit->recommendations ?? [],
            'additional_info' => $audit->additional_info,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        // Optionally save to audit
        if ($request->boolean('save')) {
            $audit->update(['sm_synthesis' => $synthesis]);
        }

        return response()->json([
            'synthesis' => $synthesis,
            'saved' => $request->boolean('save'),
        ]);
    }

    /**
     * Schedule reminders for the audit
     */
    public function scheduleReminders(Request $request, int $id): JsonResponse
    {
        $audit = $this->findScopedAuditOrFail($id);
        $this->authorize('update', $audit);

        $validated = $request->validate([
            'reminder_enabled' => 'boolean',
            'reminder_days_before' => 'nullable|array',
            'reminder_days_before.*' => 'integer|min:1|max:90',
        ]);

        $audit->update($validated);

        // Create reminder entries
        if ($validated['reminder_enabled'] && !empty($validated['reminder_days_before'])) {
            foreach ($validated['reminder_days_before'] as $days) {
                $reminderDate = $audit->planned_date->subDays($days);

                if ($reminderDate->isFuture()) {
                    $audit->reminders()->updateOrCreate(
                        [
                            'reminder_type' => 'custom',
                            'scheduled_for' => $reminderDate,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Rappels configurés',
            'reminders' => $audit->reminders,
        ]);
    }

    private function errorResponse(string $code, string $message, array $details = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    private function loadAuditWithRelations(int $id): Audit
    {
        return Audit::with([
            'site',
            'leadAuditor',
            'axes',
            'workflowState',
            'nonConformities',
            'actions',
            'processes',
            'program.site',
            'program.programManager',
            'auditors',
            'auditees',
        ])->findOrFail($id);
    }
}
