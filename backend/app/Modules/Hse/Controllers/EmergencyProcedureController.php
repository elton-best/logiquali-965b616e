<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hse\Models\EmergencyProcedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmergencyProcedureController extends Controller
{
    /**
     * Liste des situations d'urgence et procédures (REQ-8.2-01).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $query = EmergencyProcedure::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->with(['site:id,name', 'responsible:id,name,email']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('emergency_type')) {
            $query->where('emergency_type', $request->query('emergency_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $items = $query->orderBy('deadline', 'asc')->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Création d'une situation d'urgence (REQ-8.2-01).
     * Colonnes requises : Situation d'urgence — Mesures de préparation/réponse — Responsable — Délai.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'emergency_type' => 'required|string|max:100', // incendie, deversement, explosion, malaise, etc.
            'title' => 'required|string|max:255', // Libellé de la situation d'urgence
            'measures' => 'required|string', // Mesures de préparation et de réponse
            'responsible_id' => 'required|exists:users,id', // Responsable
            'deadline' => 'required|date', // Délai / Échéance de mise en place / révision
            'status' => 'nullable|in:planifie,operationnel,teste,a_reviser',
            'procedure_steps' => 'nullable|array',
            'emergency_contacts' => 'nullable|array',
            'required_equipment' => 'nullable|array',
            'training_required' => 'nullable|boolean',
            'next_drill_date' => 'nullable|date',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['created_by'] = $user->id;
        $validated['status'] = $validated['status'] ?? 'operationnel';

        $procedure = EmergencyProcedure::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Situation d\'urgence enregistrée avec succès.',
            'data' => $procedure->load(['site:id,name', 'responsible:id,name']),
        ], 201);
    }

    /**
     * Affichage d'une situation d'urgence.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $procedure = EmergencyProcedure::where('enterprise_id', $user->enterprise_id)
            ->with(['site:id,name', 'responsible:id,name,email'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $procedure,
        ]);
    }

    /**
     * Mise à jour d'une situation d'urgence.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $procedure = EmergencyProcedure::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'emergency_type' => 'sometimes|required|string|max:100',
            'title' => 'sometimes|required|string|max:255',
            'measures' => 'sometimes|required|string',
            'responsible_id' => 'sometimes|required|exists:users,id',
            'deadline' => 'sometimes|required|date',
            'status' => 'nullable|in:planifie,operationnel,teste,a_reviser',
            'procedure_steps' => 'nullable|array',
            'emergency_contacts' => 'nullable|array',
            'required_equipment' => 'nullable|array',
            'training_required' => 'nullable|boolean',
            'last_drill_date' => 'nullable|date',
            'next_drill_date' => 'nullable|date',
            'drill_report' => 'nullable|string',
        ]);

        $validated['updated_by'] = $user->id;
        $procedure->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Situation d\'urgence mise à jour.',
            'data' => $procedure->fresh(['site:id,name', 'responsible:id,name']),
        ]);
    }

    /**
     * Enregistrement d'un exercice ou simulation (REQ-8.2-02 Reco Expert).
     */
    public function recordDrill(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $procedure = EmergencyProcedure::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'drill_date' => 'required|date',
            'drill_report' => 'required|string',
            'next_drill_date' => 'nullable|date|after:drill_date',
        ]);

        $procedure->update([
            'last_drill_date' => $validated['drill_date'],
            'drill_report' => $validated['drill_report'],
            'next_drill_date' => $validated['next_drill_date'] ?? null,
            'status' => 'teste',
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exercice de simulation enregistré avec succès.',
            'data' => $procedure->fresh(['site:id,name', 'responsible:id,name']),
        ]);
    }

    /**
     * Suppression d'une situation d'urgence.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $procedure = EmergencyProcedure::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $procedure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Situation d\'urgence supprimée.',
        ]);
    }
}
