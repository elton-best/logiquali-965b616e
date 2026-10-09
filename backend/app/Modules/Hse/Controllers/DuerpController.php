<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hse\Models\Duerp;
use App\Modules\Hse\Models\DuerpDanger;
use App\Modules\Hse\Models\DuerpRiskFamily;
use App\Modules\Hse\Models\DuerpScoringScale;
use App\Modules\Hse\Models\DuerpWorkUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DuerpController extends Controller
{
    /**
     * Get list of DUERP records for the enterprise.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $query = Duerp::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->with([
                'site:id,name',
                'managingProcess:id,code,title',
                'submitter:id,name',
                'verifier:id,name',
                'approver:id,name',
            ])
            ->withCount(['dangers', 'workUnits']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('managing_process_id')) {
            $query->where('managing_process_id', $request->query('managing_process_id'));
        }

        $duerps = $query->orderByDesc('is_current')->orderByDesc('evaluation_date')->get();

        return response()->json([
            'success' => true,
            'data' => $duerps,
        ]);
    }

    /**
     * Export DUERP to Excel (by enterprise and by site).
     */
    public function exportXlsx(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $siteId = $request->filled('site_id') ? (int) $request->site_id : ($user->site_id ?: null);
        $duerpId = $request->filled('duerp_id') ? (int) $request->duerp_id : null;
        $exportService = app(\App\Modules\Hse\Services\HseExportService::class);
        $tempFile = $exportService->exportDuerpXlsx($user->enterprise_id, $siteId, $duerpId);

        $siteSuffix = $siteId ? "_site_{$siteId}" : '_tous_sites';
        $filename = 'duerp_iso45001' . $siteSuffix . '_' . date('Ymd_His') . '.xlsx';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a new DUERP version.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'managing_process_id' => 'nullable|exists:processes,id',
            'title' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'evaluation_date' => 'required|date',
            'next_evaluation_date' => 'nullable|date',
            'work_unit_definition' => 'nullable|in:process,site,custom',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['created_by'] = $user->id;
        $validated['workflow_status'] = 'draft';
        $validated['is_current'] = true;

        // If setting this as current, mark others as not current
        Duerp::where('enterprise_id', $user->enterprise_id)
            ->where('site_id', $validated['site_id'] ?? null)
            ->update(['is_current' => false]);

        $duerp = Duerp::create($validated);
        $duerp->load(['site:id,name', 'managingProcess:id,code,title']);

        return response()->json([
            'success' => true,
            'message' => 'DUERP initialisé avec succès.',
            'data' => $duerp,
        ], 201);
    }

    /**
     * Get detailed DUERP with all dangers and risk evaluations.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)
            ->with([
                'site:id,name',
                'managingProcess:id,code,title',
                'submitter:id,name',
                'verifier:id,name',
                'approver:id,name',
                'workUnits',
                'dangers.process:id,code,title',
                'dangers.workUnit:id,name,code',
                'dangers.riskFamily:id,name,code',
                'dangers.responsible:id,name',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $duerp,
        ]);
    }

    /**
     * Get tree representation of DUERP according to Canevas:
     * Unité de travail -> Familles de risques -> Risques/Dangers identifiés.
     */
    public function tree(int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)
            ->with([
                'site:id,name',
                'managingProcess:id,code,title',
                'dangers' => function ($q) {
                    $q->with(['workUnit', 'riskFamily', 'process:id,code,title', 'responsible:id,name']);
                },
            ])
            ->findOrFail($id);

        $grouped = [];
        foreach ($duerp->dangers as $danger) {
            $unitName = $danger->work_unit ?: ($danger->workUnit?->name ?: ($duerp->work_unit_definition === 'process' && $danger->process ? $danger->process->title : 'Unité générale'));
            $familyName = $danger->inrs_family ?: ($danger->riskFamily?->name ?: 'Autres risques');

            if (!isset($grouped[$unitName])) {
                $grouped[$unitName] = [
                    'work_unit' => $unitName,
                    'work_unit_id' => $danger->work_unit_id,
                    'headcount' => $danger->workUnit?->headcount ?? 1,
                    'families' => [],
                ];
            }

            if (!isset($grouped[$unitName]['families'][$familyName])) {
                $grouped[$unitName]['families'][$familyName] = [
                    'family_name' => $familyName,
                    'family_id' => $danger->risk_family_id,
                    'dangers' => [],
                ];
            }

            $grouped[$unitName]['families'][$familyName]['dangers'][] = $danger;
        }

        $tree = array_values(array_map(function ($unit) {
            $unit['families'] = array_values($unit['families']);
            return $unit;
        }, $grouped));

        return response()->json([
            'success' => true,
            'duerp' => [
                'id' => $duerp->id,
                'title' => $duerp->title,
                'version' => $duerp->version,
                'work_unit_definition' => $duerp->work_unit_definition,
                'managing_process' => $duerp->managingProcess,
                'workflow_status' => $duerp->workflow_status,
                'total_dangers_count' => $duerp->dangers->count(),
            ],
            'data' => $tree,
        ]);
    }

    /**
     * Update DUERP metadata.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'version' => 'sometimes|string|max:50',
            'evaluation_date' => 'sometimes|date',
            'next_evaluation_date' => 'nullable|date',
            'managing_process_id' => 'nullable|exists:processes,id',
            'work_unit_definition' => 'nullable|in:process,site,custom',
            'is_current' => 'nullable|boolean',
        ]);

        $validated['updated_by'] = $user->id;
        $duerp->update($validated);
        $duerp->load(['site:id,name', 'managingProcess:id,code,title']);

        return response()->json([
            'success' => true,
            'message' => 'DUERP mis à jour avec succès.',
            'data' => $duerp,
        ]);
    }

    /**
     * Delete DUERP.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $duerp->delete();

        return response()->json([
            'success' => true,
            'message' => 'DUERP supprimé avec succès.',
        ]);
    }

    // =========================================================================
    // WORKFLOW DE VALIDATION PAR LE RQ ET LA DIRECTION
    // =========================================================================

    /**
     * Soumission du DUERP pour validation par le RQ (Workflow).
     */
    public function submitForVerification(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $duerp->update([
            'workflow_status' => 'submitted_by_rq',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
            'submission_notes' => $request->input('notes', 'DUERP finalisé et soumis pour validation par le Responsable Qualité / SST.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'DUERP soumis avec succès pour validation par la Direction.',
            'data' => $duerp->fresh(['submitter:id,name', 'site:id,name']),
        ]);
    }

    /**
     * Approbation officielle du DUERP par la Direction Générale / Validateur.
     */
    public function approveByCeo(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $duerp->update([
            'workflow_status' => 'approved_by_ceo',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('notes', 'DUERP approuvé officiellement par la Direction Générale.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'DUERP validé et approuvé officiellement par la Direction Générale.',
            'data' => $duerp->fresh(['approver:id,name', 'site:id,name']),
        ]);
    }

    /**
     * Rejet du DUERP avec motif pour corrections par le RQ.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $duerp->update([
            'workflow_status' => 'draft',
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'DUERP retourné en brouillon pour corrections.',
            'data' => $duerp->fresh(['rejecter:id,name', 'site:id,name']),
        ]);
    }

    // =========================================================================
    // DANGERS & RISQUES DU DUERP
    // =========================================================================

    /**
     * Add danger / risk item to DUERP.
     */
    public function storeDanger(Request $request, int $duerpId): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($duerpId);

        $validated = $request->validate([
            'work_unit_id' => 'nullable|exists:duerp_work_units,id',
            'risk_family_id' => 'nullable|exists:duerp_risk_families,id',
            'process_id' => 'nullable|exists:processes,id',
            'work_unit' => 'nullable|string|max:255',
            'activity' => 'nullable|string|max:255',
            'activity_name' => 'nullable|string|max:255',
            'inrs_family' => 'nullable|string|max:255',
            'dangerous_situation' => 'nullable|string',
            'identified_risks' => 'nullable|string',
            'consequences' => 'nullable|string',
            'gravity' => 'required|numeric|min:0.5',
            'frequency' => 'required|numeric|min:0.5',
            'existing_preventions' => 'nullable|string',
            'mitigation_level' => 'nullable|string|max:100',
            'mitigation_coef' => 'nullable|numeric|min:0.1',
            'prevention_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'action_status' => 'nullable|in:en_continu,en_cours,realise',
            'action_evaluation_date' => 'nullable|date',
            'action_effectivity_criteria' => 'nullable|string',
            'action_efficacy_criteria' => 'nullable|string',
            'observations' => 'nullable|string',
            'residual_gravity' => 'nullable|numeric|min:0.5',
            'residual_frequency' => 'nullable|numeric|min:0.5',
        ]);

        // Auto-synchronisation des libellés si relations fournies
        if (!empty($validated['work_unit_id']) && empty($validated['work_unit'])) {
            $unit = DuerpWorkUnit::find($validated['work_unit_id']);
            $validated['work_unit'] = $unit?->name;
        }
        if (!empty($validated['risk_family_id']) && empty($validated['inrs_family'])) {
            $fam = DuerpRiskFamily::find($validated['risk_family_id']);
            $validated['inrs_family'] = $fam?->name;
        }

        $validated['duerp_id'] = $duerp->id;
        $validated['danger_type'] = $validated['inrs_family'] ?? 'Risque identifié';
        $validated['danger_description'] = $validated['dangerous_situation'] ?? $validated['danger_type'];
        $validated['created_by'] = $user->id;

        $danger = DuerpDanger::create($validated);
        $danger->load(['process:id,code,title', 'workUnit:id,name,code', 'riskFamily:id,name,code', 'responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Risque enregistré dans le DUERP avec succès.',
            'data' => $danger,
        ], 201);
    }

    /**
     * Update danger / risk item.
     */
    public function updateDanger(Request $request, int $dangerId): JsonResponse
    {
        $user = Auth::user();
        $danger = DuerpDanger::whereHas('duerp', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($dangerId);

        $validated = $request->validate([
            'work_unit_id' => 'nullable|exists:duerp_work_units,id',
            'risk_family_id' => 'nullable|exists:duerp_risk_families,id',
            'process_id' => 'nullable|exists:processes,id',
            'work_unit' => 'nullable|string|max:255',
            'activity' => 'nullable|string|max:255',
            'activity_name' => 'nullable|string|max:255',
            'inrs_family' => 'sometimes|nullable|string|max:255',
            'dangerous_situation' => 'nullable|string',
            'identified_risks' => 'nullable|string',
            'consequences' => 'nullable|string',
            'gravity' => 'sometimes|required|numeric|min:0.5',
            'frequency' => 'sometimes|required|numeric|min:0.5',
            'existing_preventions' => 'nullable|string',
            'mitigation_level' => 'nullable|string|max:100',
            'mitigation_coef' => 'nullable|numeric|min:0.1',
            'prevention_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'action_status' => 'nullable|in:en_continu,en_cours,realise',
            'action_evaluation_date' => 'nullable|date',
            'action_effectivity_criteria' => 'nullable|string',
            'action_efficacy_criteria' => 'nullable|string',
            'observations' => 'nullable|string',
            'residual_gravity' => 'nullable|numeric|min:0.5',
            'residual_frequency' => 'nullable|numeric|min:0.5',
        ]);

        if (!empty($validated['work_unit_id']) && empty($validated['work_unit'])) {
            $unit = DuerpWorkUnit::find($validated['work_unit_id']);
            $validated['work_unit'] = $unit?->name;
        }
        if (!empty($validated['risk_family_id']) && empty($validated['inrs_family'])) {
            $fam = DuerpRiskFamily::find($validated['risk_family_id']);
            $validated['inrs_family'] = $fam?->name;
        }

        $validated['updated_by'] = $user->id;
        $danger->update($validated);
        $danger->load(['process:id,code,title', 'workUnit:id,name,code', 'riskFamily:id,name,code', 'responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Ligne du DUERP mise à jour avec succès.',
            'data' => $danger,
        ]);
    }

    /**
     * Delete danger / risk item.
     */
    public function destroyDanger(int $dangerId): JsonResponse
    {
        $user = Auth::user();
        $danger = DuerpDanger::whereHas('duerp', function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id);
        })->findOrFail($dangerId);

        $danger->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ligne supprimée du DUERP.',
        ]);
    }

    // =========================================================================
    // FAMILLES / TYPES DE RISQUES PERSONNALISABLES (PERMISSION-BASED)
    // =========================================================================

    /**
     * Liste des familles de risques (globales INRS + spécifiques entreprise).
     */
    public function riskFamilies(Request $request): JsonResponse
    {
        $user = Auth::user();
        $enterpriseId = $user?->enterprise_id;

        $families = DuerpRiskFamily::where(function ($q) use ($enterpriseId) {
            $q->whereNull('enterprise_id');
            if ($enterpriseId) {
                $q->orWhere('enterprise_id', $enterpriseId);
            }
        })
        ->where('is_active', true)
        ->orderBy('order_num')
        ->orderBy('name')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $families,
        ]);
    }

    /**
     * Ajouter une nouvelle famille / type de risque pour l'entreprise.
     */
    public function storeRiskFamily(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order_num' => 'nullable|integer',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['is_active'] = true;

        $family = DuerpRiskFamily::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Nouveau type de risque ajouté avec succès.',
            'data' => $family,
        ], 201);
    }

    /**
     * Modifier une famille de risque de l'entreprise.
     */
    public function updateRiskFamily(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $family = DuerpRiskFamily::where(function ($q) use ($user) {
            $q->where('enterprise_id', $user->enterprise_id)->orWhereNull('enterprise_id');
        })->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order_num' => 'nullable|integer',
        ]);

        // Si modification d'une famille globale, cloner pour l'entreprise
        if ($family->enterprise_id === null) {
            $newFamily = DuerpRiskFamily::create(array_merge($family->toArray(), $validated, [
                'enterprise_id' => $user->enterprise_id,
            ]));
            return response()->json([
                'success' => true,
                'message' => 'Famille de risques personnalisée pour l’entreprise.',
                'data' => $newFamily,
            ]);
        }

        $family->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Famille de risques mise à jour.',
            'data' => $family,
        ]);
    }

    /**
     * Désactiver ou supprimer une famille de risque pour l'entreprise.
     */
    public function destroyRiskFamily(int $id): JsonResponse
    {
        $user = Auth::user();
        $family = DuerpRiskFamily::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $family->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type de risque retiré.',
        ]);
    }

    // =========================================================================
    // ÉCHELLES DE COTATION DYNAMIQUES (GRAVITÉ & FRÉQUENCE)
    // =========================================================================

    /**
     * Récupère les échelles de cotation actuelles (gravité, fréquence, maîtrise).
     */
    public function scoringScales(): JsonResponse
    {
        $user = Auth::user();
        $enterpriseId = $user?->enterprise_id;

        $customScales = DuerpScoringScale::where('enterprise_id', $enterpriseId)
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        if ($customScales->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'is_custom' => true,
                'data' => [
                    'gravity' => $customScales->where('scale_type', 'gravity')->values(),
                    'frequency' => $customScales->where('scale_type', 'frequency')->values(),
                    'maitrise' => $customScales->where('scale_type', 'maitrise')->values(),
                ],
            ]);
        }

        $defaultScales = DuerpScoringScale::whereNull('enterprise_id')
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        return response()->json([
            'success' => true,
            'is_custom' => false,
            'data' => [
                'gravity' => $defaultScales->where('scale_type', 'gravity')->values(),
                'frequency' => $defaultScales->where('scale_type', 'frequency')->values(),
                'maitrise' => $defaultScales->where('scale_type', 'maitrise')->values(),
            ],
        ]);
    }

    /**
     * Définir / personnaliser les échelles de cotation pour l'entreprise.
     */
    public function updateScoringScales(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'gravity' => 'nullable|array|min:1',
            'gravity.*.level' => 'required|integer|min:1',
            'gravity.*.label' => 'required|string|max:100',
            'gravity.*.score_value' => 'required|numeric|min:0.5',
            'gravity.*.consequences' => 'nullable|string',
            'gravity.*.description' => 'nullable|string',

            'frequency' => 'nullable|array|min:1',
            'frequency.*.level' => 'required|integer|min:1',
            'frequency.*.label' => 'required|string|max:100',
            'frequency.*.score_value' => 'required|numeric|min:0.5',
            'frequency.*.cadence' => 'nullable|string',
            'frequency.*.description' => 'nullable|string',

            'maitrise' => 'nullable|array',
            'maitrise.*.level' => 'required|integer|min:1',
            'maitrise.*.label' => 'required|string|max:100',
            'maitrise.*.score_value' => 'required|numeric|min:0.1',
            'maitrise.*.description' => 'nullable|string',
        ]);

        DuerpScoringScale::where('enterprise_id', $user->enterprise_id)->delete();

        $savedCount = 0;
        foreach (['gravity', 'frequency', 'maitrise'] as $scaleType) {
            if (!empty($validated[$scaleType])) {
                foreach ($validated[$scaleType] as $item) {
                    DuerpScoringScale::create([
                        'enterprise_id' => $user->enterprise_id,
                        'scale_type' => $scaleType,
                        'level' => $item['level'],
                        'label' => $item['label'],
                        'score_value' => $item['score_value'],
                        'cadence' => $item['cadence'] ?? null,
                        'consequences' => $item['consequences'] ?? null,
                        'description' => $item['description'] ?? null,
                        'is_active' => true,
                    ]);
                    $savedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Échelles de cotation DUERP enregistrées avec succès.',
            'items_count' => $savedCount,
        ]);
    }

    // =========================================================================
    // UNITÉS DE TRAVAIL (WORK UNITS) PERSONNALISÉES
    // =========================================================================

    /**
     * Liste des unités de travail de l'entreprise.
     */
    public function workUnits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = DuerpWorkUnit::where('enterprise_id', $user->enterprise_id)
            ->with(['process:id,code,title', 'manager:id,name', 'site:id,name'])
            ->withCount('dangers');

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        if ($request->filled('duerp_id')) {
            $query->where('duerp_id', $request->duerp_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Créer une nouvelle unité de travail.
     */
    public function storeWorkUnit(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'site_id' => 'nullable|exists:sites,id',
            'duerp_id' => 'nullable|exists:duerp,id',
            'process_id' => 'nullable|exists:processes,id',
            'manager_id' => 'nullable|exists:users,id',
            'headcount' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $unit = DuerpWorkUnit::create($validated);
        $unit->load(['process:id,code,title', 'manager:id,name', 'site:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Unité de travail créée avec succès.',
            'data' => $unit,
        ], 201);
    }

    /**
     * Modifier une unité de travail.
     */
    public function updateWorkUnit(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $unit = DuerpWorkUnit::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'site_id' => 'nullable|exists:sites,id',
            'duerp_id' => 'nullable|exists:duerp,id',
            'process_id' => 'nullable|exists:processes,id',
            'manager_id' => 'nullable|exists:users,id',
            'headcount' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $unit->update($validated);
        $unit->load(['process:id,code,title', 'manager:id,name', 'site:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Unité de travail mise à jour avec succès.',
            'data' => $unit,
        ]);
    }

    /**
     * Supprimer une unité de travail.
     */
    public function destroyWorkUnit(int $id): JsonResponse
    {
        $user = Auth::user();
        $unit = DuerpWorkUnit::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unité de travail supprimée.',
        ]);
    }
}
