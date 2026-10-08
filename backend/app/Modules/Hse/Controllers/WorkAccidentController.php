<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hse\Models\WorkAccident;
use App\Modules\Hse\Models\DuerpDanger;
use App\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkAccidentController extends Controller
{
    /**
     * Liste des accidents et incidents SST (REQ-6.1-D08 / D09).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $query = WorkAccident::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->with(['site:id,name', 'process:id,title,code', 'duerpDanger:id,inrs_family,dangerous_situation', 'correctiveAction:id,title,status,deadline', 'investigator:id,name']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->query('process_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('year')) {
            $query->whereYear('accident_date', (int) $request->query('year'));
        }

        $accidents = $query->orderByDesc('accident_date')->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $accidents,
        ]);
    }

    /**
     * Déclaration d'un accident ou incident SST.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'duerp_danger_id' => 'nullable|exists:duerp_dangers,id',
            'type' => 'required|string|in:accident_avec_arret,accident_sans_arret,accident_trajet,presqu_accident,incident_materiel',
            'accident_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'victim_name' => 'nullable|string|max:255',
            'victim_job_title' => 'nullable|string|max:255',
            'victim_seniority_months' => 'nullable|integer|min:0',
            'circumstances' => 'required|string',
            'nature_of_injury' => 'nullable|string|max:255',
            'location_of_injury' => 'nullable|string|max:255',
            'material_agent' => 'nullable|string|max:255',
            'lost_days_count' => 'nullable|integer|min:0',
            'severity_level' => 'nullable|in:benin,moyen,grave,mortel',
            'root_cause_analysis' => 'nullable|array',
            'preventive_recommendations' => 'nullable|string',
            'investigator_id' => 'nullable|exists:users,id',
            'investigation_date' => 'nullable|date',
            'create_corrective_action' => 'nullable|boolean',
            'action_responsible_id' => 'nullable|exists:users,id',
            'action_deadline' => 'nullable|date',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['created_by'] = $user->id;
        $validated['status'] = 'declare';

        // Créer l'accident SST
        $accident = WorkAccident::create($validated);

        // Si demandé, créer automatiquement l'action corrective liée
        if ($request->boolean('create_corrective_action') && !empty($validated['preventive_recommendations'])) {
            $action = Action::create([
                'enterprise_id' => $user->enterprise_id,
                'site_id' => $validated['site_id'] ?? $user->site_id,
                'process_id' => $validated['process_id'] ?? null,
                'type' => 'corrective',
                'title' => "Action corrective suite à accident SST {$accident->ref}",
                'description' => $validated['preventive_recommendations'],
                'source' => 'non_conformity',
                'initiator_id' => $user->id,
                'responsible_id' => $validated['action_responsible_id'] ?? $user->id,
                'deadline' => $validated['action_deadline'] ?? now()->addDays(30),
                'status' => 'planned',
            ]);

            $accident->update(['corrective_action_id' => $action->id]);
        }

        // Si lié à une ligne DUERP, mettre à jour la révision du risque lié (REQ-6.1-D09)
        if ($accident->duerp_danger_id) {
            $danger = DuerpDanger::find($accident->duerp_danger_id);
            if ($danger && $accident->lost_days_count > 0 && ($danger->gravity ?? 1) < 3) {
                // Suggérer ou rehausser la gravité suite à accident réel
                $danger->update([
                    'gravity' => max((int) $danger->gravity, 3),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Accident / incident SST enregistré avec succès.',
            'data' => $accident->load(['site:id,name', 'process:id,title,code', 'correctiveAction:id,title,status']),
        ], 201);
    }

    /**
     * Affichage d'un accident SST.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $accident = WorkAccident::where('enterprise_id', $user->enterprise_id)
            ->with(['site:id,name', 'process:id,title,code', 'duerpDanger', 'correctiveAction', 'investigator:id,name', 'closedByUser:id,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $accident,
        ]);
    }

    /**
     * Mise à jour d'un accident SST (enquête, analyse des causes).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $accident = WorkAccident::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'process_id' => 'nullable|exists:processes,id',
            'duerp_danger_id' => 'nullable|exists:duerp_dangers,id',
            'type' => 'sometimes|string|in:accident_avec_arret,accident_sans_arret,accident_trajet,presqu_accident,incident_materiel',
            'accident_date' => 'sometimes|date',
            'location' => 'nullable|string|max:255',
            'victim_name' => 'nullable|string|max:255',
            'victim_job_title' => 'nullable|string|max:255',
            'victim_seniority_months' => 'nullable|integer|min:0',
            'circumstances' => 'sometimes|string',
            'nature_of_injury' => 'nullable|string|max:255',
            'location_of_injury' => 'nullable|string|max:255',
            'material_agent' => 'nullable|string|max:255',
            'lost_days_count' => 'nullable|integer|min:0',
            'severity_level' => 'nullable|in:benin,moyen,grave,mortel',
            'root_cause_analysis' => 'nullable|array',
            'preventive_recommendations' => 'nullable|string',
            'investigator_id' => 'nullable|exists:users,id',
            'investigation_date' => 'nullable|date',
            'status' => 'nullable|in:declare,en_enquete,actions_en_cours,resolu,cloture',
        ]);

        $validated['updated_by'] = $user->id;
        $accident->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Accident SST mis à jour.',
            'data' => $accident->fresh(['site:id,name', 'process:id,title,code', 'correctiveAction', 'duerpDanger']),
        ]);
    }

    /**
     * Clôture de l'accident SST.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $accident = WorkAccident::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'closure_notes' => 'required|string',
        ]);

        $accident->update([
            'status' => 'cloture',
            'closure_notes' => $validated['closure_notes'],
            'closed_at' => now(),
            'closed_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dossier d\'accident SST clôturé.',
            'data' => $accident,
        ]);
    }

    /**
     * Statistiques ISO 45001 : TF (Taux de fréquence) & TG (Taux de gravité).
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $year = $request->integer('year', (int) date('Y'));
        $siteId = $request->filled('site_id') ? $request->integer('site_id') : null;

        $baseQuery = WorkAccident::where('enterprise_id', $user->enterprise_id)
            ->whereYear('accident_date', $year);

        if ($siteId) {
            $baseQuery->where('site_id', $siteId);
        }

        $totalAccidents = (clone $baseQuery)->count();
        $withLostTime = (clone $baseQuery)->where('type', 'accident_avec_arret')->count();
        $withoutLostTime = (clone $baseQuery)->where('type', 'accident_sans_arret')->count();
        $nearMisses = (clone $baseQuery)->where('type', 'presqu_accident')->count();
        $totalLostDays = (clone $baseQuery)->sum('lost_days_count');

        // Heures travaillées théoriques par défaut (ex: 200 000 h pour une PME de 100 salariés)
        $workedHours = $request->integer('worked_hours', 200000);

        // Formules ISO 45001 / INRS :
        // TF = (Nombre d'accidents avec arrêt * 1 000 000) / Heures travaillées
        $tf = $workedHours > 0 ? round(($withLostTime * 1000000) / $workedHours, 2) : 0;
        // TG = (Nombre de jours d'arrêt * 1 000) / Heures travaillées
        $tg = $workedHours > 0 ? round(($totalLostDays * 1000) / $workedHours, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'total_accidents' => $totalAccidents,
                'with_lost_time' => $withLostTime,
                'without_lost_time' => $withoutLostTime,
                'near_misses' => $nearMisses,
                'total_lost_days' => $totalLostDays,
                'frequency_rate_tf' => $tf,
                'severity_rate_tg' => $tg,
                'types' => WorkAccident::TYPES,
                'severities' => WorkAccident::SEVERITIES,
                'statuses' => WorkAccident::STATUSES,
            ],
        ]);
    }
}
