<?php

namespace App\Modules\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModificationResource;
use App\Models\Action;
use App\Models\Document;
use App\Models\Modification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Modification::with(['site:id,name', 'initiator:id,name', 'responsible:id,name', 'rqVerifier:id,name', 'ceoApprover:id,name']);

        if ($user && $user->enterprise_id) {
            $query->where('enterprise_id', $user->enterprise_id);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->query('workflow_status'));
        }

        $modifications = $query->orderByDesc('date')->paginate($request->integer('per_page', 20));
        return ModificationResource::collection($modifications);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'object' => 'required|string|max:255',
            'description' => 'required|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'affected_document_ids' => 'nullable|array',
            'affected_document_ids.*' => 'exists:documents,id',
            'responsible_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:pending,approved,implemented,rejected',
        ]);

        $validated['enterprise_id'] = $user?->enterprise_id;
        $validated['initiator_id'] = $user?->id;
        $validated['workflow_status'] = 'brouillon'; // REQ-6.3-02 : Démarre en brouillon
        $validated['number'] = $validated['number'] ?? ('MOD-' . date('Ymd-His'));

        $modification = Modification::create($validated);
        return new ModificationResource($modification->load(['site', 'initiator', 'responsible']));
    }

    public function show(Modification $modification)
    {
        return new ModificationResource($modification->load(['site', 'initiator', 'responsible', 'rqVerifier', 'ceoApprover']));
    }

    public function update(Request $request, Modification $modification)
    {
        $user = Auth::user();

        // REQ-6.3-01 : Si un responsable est désigné et que la modification est en cours,
        // le responsable ne peut modifier que sa partie
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'number' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'object' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'affected_document_ids' => 'nullable|array',
            'affected_document_ids.*' => 'exists:documents,id',
            'responsible_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:pending,approved,implemented,rejected',
            'modification_results' => 'nullable|string',
            'surveillance_results' => 'nullable|string',
        ]);

        $modification->update($validated);
        return new ModificationResource($modification->load(['site', 'initiator', 'responsible', 'rqVerifier', 'ceoApprover']));
    }

    /**
     * Soumission de la demande pour vérification par le RQ (REQ-6.3-03).
     */
    public function submitForVerification(int $id): JsonResponse
    {
        $modification = Modification::findOrFail($id);
        $modification->update([
            'workflow_status' => 'en_cours',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de modification soumise pour vérification par le RQ.',
            'data' => new ModificationResource($modification->load(['site', 'initiator', 'responsible'])),
        ]);
    }

    /**
     * Vérification par le Responsable Qualité (RQ) avec ajout d'actions optionnelles (REQ-6.3-03).
     */
    public function verifyByRq(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $modification = Modification::findOrFail($id);

        $validated = $request->validate([
            'rq_notes' => 'nullable|string|max:2000',
            'additional_actions' => 'nullable|array',
            'additional_actions.*.title' => 'required|string|max:255',
            'additional_actions.*.responsible_id' => 'required|exists:users,id',
            'additional_actions.*.deadline' => 'required|date',
        ]);

        $modification->update([
            'workflow_status' => 'verifie_rq',
            'rq_verified_by' => $user?->id,
            'rq_verified_at' => now(),
            'rq_notes' => $validated['rq_notes'] ?? null,
        ]);

        // Si le RQ a ajouté des actions dans le cadre de la modification
        if (!empty($validated['additional_actions'])) {
            foreach ($validated['additional_actions'] as $actionData) {
                Action::create([
                    'enterprise_id' => $modification->enterprise_id,
                    'site_id' => $modification->site_id,
                    'type' => 'improvement',
                    'title' => $actionData['title'],
                    'description' => "Action issue de la demande de modification {$modification->number}",
                    'source' => 'suggestion',
                    'initiator_id' => $user?->id,
                    'responsible_id' => $actionData['responsible_id'],
                    'deadline' => $actionData['deadline'],
                    'status' => 'planned',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Modification vérifiée par le RQ et transmise à la Direction Générale (CEO).',
            'data' => new ModificationResource($modification->load(['site', 'initiator', 'responsible', 'rqVerifier'])),
        ]);
    }

    /**
     * Approbation finale par le CEO / Direction Générale (REQ-6.3-03 / REQ-6.3-04).
     * Incrémente la version des documents concernés lors de l'approbation formelle.
     */
    public function approveByCeo(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $modification = Modification::findOrFail($id);

        $validated = $request->validate([
            'ceo_notes' => 'nullable|string|max:2000',
        ]);

        $modification->update([
            'workflow_status' => 'approuve_ceo',
            'status' => 'approved',
            'ceo_approved_by' => $user?->id,
            'ceo_approved_at' => now(),
            'ceo_notes' => $validated['ceo_notes'] ?? null,
            'validated_by' => $user?->id,
            'validated_at' => now(),
        ]);

        // REQ-6.3-04 : La version du document ne change qu'à l'approbation du CEO
        if (!empty($modification->affected_document_ids)) {
            foreach ($modification->affected_document_ids as $docId) {
                $doc = Document::find($docId);
                if ($doc) {
                    $currentVersion = (float) ($doc->version ?? 1.0);
                    $doc->update([
                        'version' => number_format($currentVersion + 0.1, 1),
                        'workflow_status' => 'approved',
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Modification du système officiellement approuvée par la Direction Générale.',
            'data' => new ModificationResource($modification->load(['site', 'initiator', 'responsible', 'rqVerifier', 'ceoApprover'])),
        ]);
    }

    /**
     * Enregistrement des résultats de modification et de surveillance (REQ-6.3-05).
     */
    public function recordResults(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $modification = Modification::findOrFail($id);

        $validated = $request->validate([
            'modification_results' => 'nullable|string', // Saisi par le responsable de la modification
            'surveillance_results' => 'nullable|string', // Saisi par le RQ
        ]);

        $modification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Résultats enregistrés avec succès.',
            'data' => new ModificationResource($modification),
        ]);
    }

    public function destroy(Modification $modification)
    {
        $modification->delete();
        return response()->json(null, 204);
    }
}
