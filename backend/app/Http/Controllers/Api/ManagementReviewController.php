<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagementReviewResource;
use App\Models\ManagementReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManagementReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:evaluation.revue_direction.read')->only([
            'index',
            'show',
            'downloadProcedureTemplate',
            'downloadInvitationTemplate',
            'getSmSynthesis',
        ]);
        $this->middleware('permission:evaluation.revue_direction.create')->only(['store']);
        $this->middleware('permission:evaluation.revue_direction.update')->only([
            'update',
            'close',
            'generateData',
            'generateSmSynthesis',
            'sendInvitations',
        ]);
        $this->middleware('permission:evaluation.revue_direction.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = ManagementReview::with(['site', 'chairman']);
        $query = $this->applyUserScope($query, $request->user());

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by quarter
        if ($request->has('quarter')) {
            $query->where('quarter', $request->quarter);
        }

        // Filter by site_id
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $reviews = $query->paginate(20);

        return ManagementReviewResource::collection($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'title' => 'nullable|string|max:255',
            'scheduled_date' => 'nullable|date',
            'planned_date' => 'nullable|date',
            'actual_date' => 'nullable|date',
            'year' => 'nullable|integer',
            'quarter' => 'nullable|string|max:10',
            'chairman_id' => 'nullable|exists:users,id',
            'participants' => 'nullable|array',
            'previous_actions_status' => 'nullable|string',
            'context_changes' => 'nullable|string',
            'performance_indicators' => 'nullable|string',
            'customer_satisfaction' => 'nullable|string',
            'audit_results' => 'nullable|string',
            'nc_complaints_status' => 'nullable|string',
            'resources_adequacy' => 'nullable|string',
            'improvement_opportunities' => 'nullable|string',
            'kpi_data' => 'nullable|array',
            'objectives_data' => 'nullable|array',
            'actions_data' => 'nullable|array',
            'risks_data' => 'nullable|array',
            'nc_data' => 'nullable|array',
            'audit_data' => 'nullable|array',
            'm12_d2_traceability' => 'nullable|array',
            'm12_d3_traceability' => 'nullable|array',
            'decisions' => 'nullable|array',
            'action_items' => 'nullable|array',
            'report_path' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,reported',
        ]);

        $review = ManagementReview::create($validated);

        return new ManagementReviewResource($review->load(['site', 'chairman']));
    }

    public function show(ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, request()->user());

        return new ManagementReviewResource($managementReview->load(['site', 'chairman']));
    }

    public function update(Request $request, ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, $request->user());

        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'title' => 'nullable|string|max:255',
            'scheduled_date' => 'nullable|date',
            'planned_date' => 'nullable|date',
            'actual_date' => 'nullable|date',
            'year' => 'nullable|integer',
            'quarter' => 'nullable|string|max:10',
            'chairman_id' => 'nullable|exists:users,id',
            'participants' => 'nullable|array',
            'previous_actions_status' => 'nullable|string',
            'context_changes' => 'nullable|string',
            'performance_indicators' => 'nullable|string',
            'customer_satisfaction' => 'nullable|string',
            'audit_results' => 'nullable|string',
            'nc_complaints_status' => 'nullable|string',
            'resources_adequacy' => 'nullable|string',
            'improvement_opportunities' => 'nullable|string',
            'kpi_data' => 'nullable|array',
            'objectives_data' => 'nullable|array',
            'actions_data' => 'nullable|array',
            'risks_data' => 'nullable|array',
            'nc_data' => 'nullable|array',
            'audit_data' => 'nullable|array',
            'm12_d2_traceability' => 'nullable|array',
            'm12_d3_traceability' => 'nullable|array',
            'decisions' => 'nullable|array',
            'action_items' => 'nullable|array',
            'report_path' => 'nullable|string',
            'status' => 'sometimes|in:planned,in_progress,completed,reported',
        ]);

        $managementReview->update($validated);

        return new ManagementReviewResource($managementReview->load(['site', 'chairman']));
    }

    public function destroy(ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, request()->user());
        $managementReview->delete();

        return response()->json(null, 204);
    }

    /**
     * Close review and generate report automatically.
     */
    public function close(Request $request, ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, $request->user());

        $generator = new \App\Services\ManagementReviewDocxGenerator;
        $temporaryPath = $generator->generateReport($managementReview);

        $targetPath = sprintf(
            'management-reviews/reports/%s_%s.docx',
            $managementReview->ref ?: ('REV-'.$managementReview->id),
            now()->format('Ymd_His')
        );

        $content = @file_get_contents($temporaryPath);
        if ($content === false) {
            return response()->json([
                'message' => 'La generation automatique du rapport a echoue.',
            ], 500);
        }

        Storage::put($targetPath, $content);
        if (is_file($temporaryPath)) {
            @unlink($temporaryPath);
        }

        $traceability = is_array($managementReview->m12_d2_traceability)
            ? $managementReview->m12_d2_traceability
            : [];

        $traceability[] = [
            'event' => 'review_closed_auto_report_generated',
            'generated_at' => now()->toISOString(),
            'generated_by' => $request->user()?->id,
            'report_path' => $targetPath,
        ];

        $managementReview->update([
            'status' => 'completed',
            'actual_date' => $managementReview->actual_date ?? now()->toDateString(),
            'report_path' => $targetPath,
            'generated_at' => now(),
            'm12_d2_traceability' => $traceability,
        ]);

        return new ManagementReviewResource($managementReview->fresh()->load(['site', 'chairman']));
    }

    /**
     * Export Management Review Report as DOCX
     */
    public function exportDocx(ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, request()->user());
        $generator = new \App\Services\ManagementReviewDocxGenerator;
        $filePath = $generator->generateReport($managementReview);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => (int) $managementReview->site_id,
            'process_id' => null,
            'process_code' => 'GEN',
            'document_kind' => 'management_review_report',
            'source_type' => 'management_review',
            'source_id' => $managementReview->id,
            'source_updated_at' => $managementReview->updated_at?->toISOString(),
            'title' => 'Rapport de revue de direction - ' . ($managementReview->reference ?? ('RD-' . $managementReview->id)),
            'description' => 'Rapport de revue de direction généré automatiquement.',
            'file_source_path' => $filePath,
            'created_by' => request()->user()?->id,
            'type' => 'ENR',
            'force_new' => true,
        ]);

        return response()
            ->download($filePath, basename($filePath))
            ->header('X-Generated-Document-Id', (string) $document->id)
            ->deleteFileAfterSend(true);
    }

    /**
     * Generate input data automatically (collect indicators, NC, audits, etc.)
     */
    public function generateData(ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, request()->user());

        $generated = $this->collectReviewDataBySite((int) $managementReview->site_id);

        $managementReview->update([
            'kpi_data' => $generated['kpis'],
            'objectives_data' => $generated['objectives'],
            'actions_data' => $generated['actions'],
            'risks_data' => $generated['risks'],
            'nc_data' => $generated['nonconformities'],
            'audit_data' => $generated['audits'],
            'generated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Données générées avec succès',
            'data' => $generated,
        ]);
    }

    /**
     * Génère la synthèse du système de management (ISO 9001:2015 §9.3.2 b, c1, c2, c4, c7, e)
     * et la stocke dans input_data.iso_9001_9_3_2.
     */
    public function generateSmSynthesis(Request $request, ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, $request->user());

        $siteId = (int) $managementReview->site_id;
        $generated = $this->collectReviewDataBySite($siteId);

        // Maintenir à jour les jeux de données d'entrée de base
        $managementReview->update([
            'kpi_data' => $generated['kpis'],
            'objectives_data' => $generated['objectives'],
            'actions_data' => $generated['actions'],
            'risks_data' => $generated['risks'],
            'nc_data' => $generated['nonconformities'],
            'audit_data' => $generated['audits'],
            'generated_at' => now(),
        ]);

        $existingInputData = is_array($managementReview->input_data) ? $managementReview->input_data : [];
        $synthesis = $this->buildSmSynthesisPayload($siteId, $managementReview->context_changes);

        $existingInputData['iso_9001_9_3_2'] = $synthesis;

        $managementReview->update([
            'input_data' => $existingInputData,
            'context_changes' => $managementReview->context_changes ?: $synthesis['entries']['b']['summary'],
            'customer_satisfaction' => $managementReview->customer_satisfaction ?: $synthesis['entries']['c1']['summary'],
            'performance_indicators' => $managementReview->performance_indicators ?: $synthesis['entries']['c4']['summary'],
            'improvement_opportunities' => $managementReview->improvement_opportunities ?: $synthesis['entries']['e']['summary'],
        ]);

        return response()->json([
            'message' => 'Synthèse du SM générée avec succès',
            'data' => $synthesis,
        ]);
    }

    /**
     * Synthèse dynamique du SM pour le site sélectionné.
     * Peut être consultée avant planification de la revue.
     */
    public function getSmSynthesis(Request $request)
    {
        $siteId = (int) ($request->integer('site_id') ?: ($request->user()?->site_id ?? 0));
        if ($siteId <= 0) {
            return response()->json([
                'message' => 'site_id est requis pour générer la synthèse du SM.',
            ], 422);
        }

        $user = $request->user();
        if (
            $user
            && ! (method_exists($user, 'isEnterpriseAdmin') && $user->isEnterpriseAdmin())
            && (int) ($user->site_id ?? 0) > 0
            && (int) $user->site_id !== $siteId
        ) {
            abort(403, 'Accès non autorisé à ce site');
        }

        $latestReview = ManagementReview::query()
            ->where('site_id', $siteId)
            ->latest('planned_date')
            ->first();

        $synthesis = $this->buildSmSynthesisPayload(
            $siteId,
            $latestReview?->context_changes
        );

        return response()->json([
            'message' => 'Synthèse dynamique du SM générée',
            'data' => $synthesis,
        ]);
    }

    /**
     * Send invitations to participants
     */
    public function sendInvitations(Request $request, ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, $request->user());

        $validated = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = collect($validated['user_ids'] ?? [])
            ->merge($managementReview->participants ?? [])
            ->when($managementReview->chairman_id, fn ($ids) => $ids->push($managementReview->chairman_id))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return response()->json([
                'message' => 'Aucun participant défini pour envoyer les invitations',
            ], 422);
        }

        $users = \App\Models\User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->notify(new \App\Notifications\ManagementReviewInvitation($managementReview));
        }

        return response()->json([
            'message' => 'Invitations envoyées',
            'sent_to' => $users->pluck('email'),
        ]);
    }

    public function downloadProcedureTemplate()
    {
        $generator = new \App\Services\ProcedureRevueDirectionDocxGenerator;
        $filePath = $generator->generate();
        $user = request()->user();

        $siteId = (int) ($user?->site_id ?? 0);
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'management_review_procedure',
                'title' => 'Procédure revue de direction',
                'description' => 'Procédure de revue de direction générée automatiquement.',
                'file_source_path' => $filePath,
                'created_by' => $user?->id,
                'type' => 'PRC',
            ]);
        }

        return response()->download(
            $filePath,
            'Procedure_Revue_Direction.docx'
        )->deleteFileAfterSend(true);
    }

    public function downloadInvitationTemplate(Request $request)
    {
        $query = ManagementReview::with(['chairman']);
        $query = $this->applyUserScope($query, $request->user());

        if ($request->filled('management_review_id')) {
            $query->where('id', $request->integer('management_review_id'));
        }

        $review = $query
            ->orderByDesc('planned_date')
            ->first();

        if (! $review) {
            return response()->json([
                'message' => 'Aucune revue disponible pour generer un modele d\'invitation',
            ], 422);
        }

        $generator = new \App\Services\InvitationRevueDocxGenerator;
        $filePath = $generator->generate($review);

        if ((int) $review->site_id > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $review->site_id,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'management_review_invitation',
                'title' => 'Invitation revue de direction - ' . ($review->reference ?? ('RD-' . $review->id)),
                'description' => 'Invitation de revue de direction générée automatiquement.',
                'file_source_path' => $filePath,
                'created_by' => $request->user()?->id,
                'type' => 'ENR',
            ]);
        }

        return response()->download(
            $filePath,
            'Invitation_Revue_Direction.docx'
        )->deleteFileAfterSend(true);
    }

    public function generateDraftDocx(Request $request, ManagementReview $managementReview)
    {
        $this->ensureAccessible($managementReview, $request->user());
        $request->validate([
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => 'nullable|integer|exists:processes,id',
        ]);

        $managementReview->load(['site.enterprise', 'chairman']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.management-review', [
            'review' => $managementReview,
            'isDraft' => true,
        ]);

        $tempPath = storage_path('app/temp/mr_draft_' . $managementReview->id . '_' . time() . '.pdf');
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0775, true);
        $pdf->save($tempPath);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => (int) $managementReview->site_id,
            'process_id' => $request->integer('process_id') ?: null,
            'process_code' => 'GEN',
            'document_kind' => 'management_review_report',
            'source_type' => 'management_review',
            'source_id' => $managementReview->id,
            'source_updated_at' => $managementReview->updated_at?->toISOString(),
            'title' => 'Rapport de revue de direction — ' . ($managementReview->title ?? 'RD-' . $managementReview->id),
            'description' => 'Rapport de revue de direction généré automatiquement.',
            'file_source_path' => $tempPath,
            'created_by' => $request->user()?->id,
            'type' => 'ENR',
            'force_new' => true,
            'store_file' => true,
            'metadata' => ['document_type_catalog_id' => $request->integer('document_type_catalog_id')],
        ]);

        @unlink($tempPath);
        return response()->json(['data' => $document]);
    }

    private function applyUserScope($query, $user)
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (method_exists($user, 'isEnterpriseAdmin') && $user->isEnterpriseAdmin()) {
            return $query;
        }

        if ($user->site_id) {
            return $query->where('site_id', $user->site_id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function ensureAccessible(ManagementReview $managementReview, $user): void
    {
        if (! $user) {
            abort(403, 'Accès non autorisé');
        }

        if (method_exists($user, 'isEnterpriseAdmin') && $user->isEnterpriseAdmin()) {
            return;
        }

        if ($user->site_id && (int) $user->site_id === (int) $managementReview->site_id) {
            return;
        }

        abort(403, 'Accès non autorisé à cette revue');
    }

    /**
     * Collecte des jeux de données de base pour la revue.
     */
    private function collectReviewDataBySite(int $siteId): array
    {
        $since = now()->subYear();

        $kpis = \App\Models\Indicateur::query()
            ->where('site_id', $siteId)
            ->latest('updated_at')
            ->take(50)
            ->get()
            ->toArray();

        $objectives = \App\Models\ProcessObjective::query()
            ->whereHas('process', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            })
            ->latest('updated_at')
            ->take(100)
            ->get()
            ->toArray();

        $actions = \App\Models\Action::query()
            ->where('site_id', $siteId)
            ->where('created_at', '>=', $since)
            ->latest('updated_at')
            ->take(200)
            ->get()
            ->toArray();

        $risks = \App\Models\ProcessRiskOpportunity::query()
            ->whereHas('process', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            })
            ->where('type', 'risque')
            ->latest('updated_at')
            ->take(200)
            ->get()
            ->toArray();

        $nonconformities = \App\Models\NonConformity::query()
            ->where('site_id', $siteId)
            ->where('created_at', '>=', $since)
            ->latest('updated_at')
            ->take(200)
            ->get()
            ->toArray();

        $audits = \App\Models\Audit::query()
            ->where('site_id', $siteId)
            ->where('planned_date', '>=', $since)
            ->latest('planned_date')
            ->take(100)
            ->get()
            ->toArray();

        return [
            'kpis' => $kpis,
            'objectives' => $objectives,
            'actions' => $actions,
            'risks' => $risks,
            'nonconformities' => $nonconformities,
            'audits' => $audits,
        ];
    }

    /**
     * Construit le payload complet de synthèse ISO 9001:2015 §9.3.2 pour un site.
     */
    private function buildSmSynthesisPayload(int $siteId, ?string $contextChanges = null): array
    {
        $generated = $this->collectReviewDataBySite($siteId);
        $since = now()->subYear();
        $enterpriseId = (int) \App\Models\Site::query()->where('id', $siteId)->value('enterprise_id');

        $customerCompleted = \App\Models\EvaluationRequest::query()
            ->where('site_id', $siteId)
            ->where('type', 'satisfaction_client')
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->count();

        $personnelCompleted = \App\Models\EvaluationRequest::query()
            ->where('site_id', $siteId)
            ->where('type', 'satisfaction_personnel')
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->count();

        $customerAvg = (float) \App\Models\EvaluationResponse::query()
            ->whereHas('request', function ($q) use ($siteId, $since) {
                $q->where('site_id', $siteId)
                    ->whereIn('type', ['satisfaction_client', 'satisfaction_personnel'])
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $since);
            })
            ->avg('percentage');

        $objectiveRows = collect($generated['objectives']);
        $objectiveTotal = $objectiveRows->count();
        $objectiveAchieved = $objectiveRows
            ->filter(function ($row) {
                $status = strtolower((string) ($row['status'] ?? ''));
                $achievement = (float) ($row['achievement_percentage'] ?? 0);
                return in_array($status, ['achieved', 'completed', 'done'], true) || $achievement >= 100;
            })
            ->count();

        $kpiRows = collect($generated['kpis']);
        $kpiTotal = $kpiRows->count();
        $kpiOnTarget = $kpiRows
            ->filter(function ($row) {
                $current = isset($row['current_value']) ? (float) $row['current_value'] : null;
                $target = isset($row['target_value']) ? (float) $row['target_value'] : null;
                if ($current === null || $target === null) {
                    return false;
                }
                return $current >= $target;
            })
            ->count();

        $majorNcCount = \App\Models\AuditFinding::query()
            ->whereHas('audit', function ($q) use ($siteId, $since) {
                $q->where('site_id', $siteId)
                    ->where('planned_date', '>=', $since);
            })
            ->where('type', 'nc_major')
            ->count();

        $providerCompleted = \App\Models\EvaluationRequest::query()
            ->where('site_id', $siteId)
            ->whereIn('type', ['satisfaction_fournisseur', 'performance_fournisseur'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->count();

        $providerAvg = (float) \App\Models\EvaluationResponse::query()
            ->whereHas('request', function ($q) use ($siteId, $since) {
                $q->where('site_id', $siteId)
                    ->whereIn('type', ['satisfaction_fournisseur', 'performance_fournisseur'])
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $since);
            })
            ->avg('percentage');

        $providerCount = $enterpriseId > 0
            ? \App\Models\ProviderPartner::query()->where('enterprise_id', $enterpriseId)->count()
            : 0;

        $riskOpportunityActions = \App\Models\Action::query()
            ->where('site_id', $siteId)
            ->where(function ($q) {
                $q->whereNotNull('risk_id')
                    ->orWhereIn('source_type', ['risk', 'risque', 'opportunity', 'opportunite'])
                    ->orWhereIn('type', ['preventive', 'corrective', 'control']);
            })
            ->where('created_at', '>=', now()->subYear())
            ->get();

        $roActionTotal = $riskOpportunityActions->count();
        $roActionEffective = $riskOpportunityActions
            ->filter(function ($row) {
                $status = strtolower((string) ($row->status ?? ''));
                $isClosed = in_array($status, ['completed', 'closed', 'done', 'resolved'], true);
                return $isClosed || (bool) $row->effectiveness_verified;
            })
            ->count();

        $contextScore = trim((string) ($contextChanges ?? '')) !== '' ? 100 : 0;
        $c1Score = max(0, min(100, round($customerAvg, 2)));
        $c2Score = $objectiveTotal > 0 ? round(($objectiveAchieved / $objectiveTotal) * 100, 2) : 0;
        $c4ScoreBase = $kpiTotal > 0 ? (($kpiOnTarget / $kpiTotal) * 100) : 0;
        $c4Score = max(0, min(100, round($c4ScoreBase - ($majorNcCount * 5), 2)));
        $c7Score = max(0, min(100, round($providerAvg, 2)));
        $eScore = $roActionTotal > 0 ? round(($roActionEffective / $roActionTotal) * 100, 2) : 0;

        return [
            'site_id' => $siteId,
            'iso_clause' => 'ISO 9001:2015 - 9.3.2',
            'generated_at' => now()->toISOString(),
            'period' => [
                'from' => $since->toDateString(),
                'to' => now()->toDateString(),
            ],
            'entries' => [
                'b' => [
                    'title' => 'b) Changements des enjeux internes et externes',
                    'summary' => $contextChanges ?: 'Aucune synthèse contextuelle rédigée. Mettre à jour les changements de contexte.',
                    'score_percent' => $contextScore,
                    'metrics' => [
                        'audits_sur_periode' => count($generated['audits']),
                        'nc_sur_periode' => count($generated['nonconformities']),
                    ],
                ],
                'c1' => [
                    'title' => 'c1) Satisfaction client et retours des parties intéressées',
                    'summary' => sprintf('%d évaluations client complétées, %d évaluations personnel complétées, moyenne %.1f%%.', $customerCompleted, $personnelCompleted, round($customerAvg, 1)),
                    'score_percent' => $c1Score,
                    'metrics' => [
                        'satisfaction_client_completed' => $customerCompleted,
                        'satisfaction_personnel_completed' => $personnelCompleted,
                        'average_score_percent' => round($customerAvg, 2),
                    ],
                ],
                'c2' => [
                    'title' => 'c2) Niveau d’atteinte des objectifs qualité',
                    'summary' => sprintf('%d objectif(s) atteint(s) sur %d (%s).', $objectiveAchieved, $objectiveTotal, $objectiveTotal > 0 ? round(($objectiveAchieved / $objectiveTotal) * 100, 1).'%' : '0%'),
                    'score_percent' => $c2Score,
                    'metrics' => [
                        'objectives_total' => $objectiveTotal,
                        'objectives_achieved' => $objectiveAchieved,
                    ],
                ],
                'c4' => [
                    'title' => 'c4) Performance des processus et conformité produits/services',
                    'summary' => sprintf('%d indicateur(s) au niveau cible sur %d ; %d NC majeures détectées.', $kpiOnTarget, $kpiTotal, $majorNcCount),
                    'score_percent' => $c4Score,
                    'metrics' => [
                        'kpi_total' => $kpiTotal,
                        'kpi_on_target' => $kpiOnTarget,
                        'major_nonconformities' => $majorNcCount,
                    ],
                ],
                'c7' => [
                    'title' => 'c7) Performance des prestataires externes',
                    'summary' => sprintf('%d prestataire(s) suivis ; %d évaluations fournisseurs complétées ; moyenne %.1f%%.', $providerCount, $providerCompleted, round($providerAvg, 1)),
                    'score_percent' => $c7Score,
                    'metrics' => [
                        'providers_total' => $providerCount,
                        'provider_evaluations_completed' => $providerCompleted,
                        'provider_average_score_percent' => round($providerAvg, 2),
                    ],
                ],
                'e' => [
                    'title' => 'e) Efficacité des actions face aux risques et opportunités',
                    'summary' => sprintf('%d action(s) liées aux risques/opportunités, dont %d traitée(s)/vérifiée(s) efficace(s).', $roActionTotal, $roActionEffective),
                    'score_percent' => $eScore,
                    'metrics' => [
                        'ro_actions_total' => $roActionTotal,
                        'ro_actions_effective' => $roActionEffective,
                    ],
                ],
            ],
        ];
    }
}
