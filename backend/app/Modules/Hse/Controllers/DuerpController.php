<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hse\Models\Duerp;
use App\Modules\Hse\Models\DuerpDanger;
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
            ->with(['site:id,name', 'verifier:id,name', 'approver:id,name'])
            ->withCount('dangers');

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
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
        $duerp->load(['site:id,name']);

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
                'verifier:id,name',
                'approver:id,name',
                'dangers.process:id,code,title',
                'dangers.responsible:id,name',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $duerp,
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
            'work_unit_definition' => 'nullable|in:process,site,custom',
            'is_current' => 'nullable|boolean',
        ]);

        $validated['updated_by'] = $user->id;
        $duerp->update($validated);

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

    /**
     * Return the 14 standard INRS risk families for dropdown.
     */
    public function inrsFamilies(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DuerpDanger::INRS_FAMILIES,
        ]);
    }

    /**
     * Add danger / risk item to DUERP.
     */
    public function storeDanger(Request $request, int $duerpId): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($duerpId);

        $validated = $request->validate([
            'process_id' => 'nullable|exists:processes,id',
            'work_unit' => 'nullable|string|max:255',
            'activity' => 'nullable|string|max:255',
            'inrs_family' => 'required|string|max:255',
            'dangerous_situation' => 'nullable|string',
            'identified_risks' => 'nullable|string',
            'consequences' => 'nullable|string',
            'gravity' => 'required|integer|min:1|max:4',
            'frequency' => 'required|integer|min:1|max:4',
            'existing_preventions' => 'nullable|string',
            'prevention_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'action_status' => 'nullable|in:en_continu,en_cours,realise',
            'residual_gravity' => 'nullable|integer|min:1|max:4',
            'residual_frequency' => 'nullable|integer|min:1|max:4',
        ]);

        $validated['duerp_id'] = $duerp->id;
        $validated['danger_type'] = $validated['inrs_family'];
        $validated['danger_description'] = $validated['dangerous_situation'] ?? $validated['inrs_family'];
        $validated['created_by'] = $user->id;

        $danger = DuerpDanger::create($validated);
        $danger->load(['process:id,code,title', 'responsible:id,name']);

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
            'process_id' => 'nullable|exists:processes,id',
            'work_unit' => 'nullable|string|max:255',
            'activity' => 'nullable|string|max:255',
            'inrs_family' => 'sometimes|required|string|max:255',
            'dangerous_situation' => 'nullable|string',
            'identified_risks' => 'nullable|string',
            'consequences' => 'nullable|string',
            'gravity' => 'sometimes|required|integer|min:1|max:4',
            'frequency' => 'sometimes|required|integer|min:1|max:4',
            'existing_preventions' => 'nullable|string',
            'prevention_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'action_status' => 'nullable|in:en_continu,en_cours,realise',
            'residual_gravity' => 'nullable|integer|min:1|max:4',
            'residual_frequency' => 'nullable|integer|min:1|max:4',
        ]);

        $validated['updated_by'] = $user->id;
        $danger->update($validated);
        $danger->load(['process:id,code,title', 'responsible:id,name']);

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

    /**
     * Submit DUERP for verification by RQ (REQ-6.1-D06).
     */
    public function submitForVerification(int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $duerp->update([
            'workflow_status' => 'verified_by_rq',
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'DUERP soumis et vérifié par le Responsable Qualité / SST.',
            'data' => $duerp,
        ]);
    }

    /**
     * Approve DUERP by CEO / Direction Générale (REQ-6.1-D06).
     */
    public function approveByCeo(int $id): JsonResponse
    {
        $user = Auth::user();
        $duerp = Duerp::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $duerp->update([
            'workflow_status' => 'approved_by_ceo',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'DUERP validé et approuvé officiellement par la Direction Générale.',
            'data' => $duerp,
        ]);
    }
}

