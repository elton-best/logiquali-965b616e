<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditProgram;
use App\Models\Audit;
use App\Models\Process;
use App\Models\Risk;
use App\Services\AuditProgramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditProgramController extends Controller
{
    public function __construct(
        protected AuditProgramService $service
    ) {}

    protected function loadProgramRelations(AuditProgram $program): AuditProgram
    {
        return $program->load([
            'site',
            'programManager',
            'validator',
            'audits.leadAuditor',
            'audits.site',
        ]);
    }

    /**
     * Liste des programmes d'audits
     * GET /api/v1/audit-programs
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditProgram::class);

        $query = AuditProgram::with(['site', 'programManager', 'audits'])
            ->latest();

        // Filtres
        if ($request->has('year')) {
            $query->forYear($request->year);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $programs = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'data' => $programs,
            'total' => $programs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator 
                ? $programs->total() 
                : $programs->count()
        ]);
    }

    /**
     * Afficher un programme
     * GET /api/v1/audit-programs/{id}
     */
    public function show(int $id)
    {
        $program = AuditProgram::with([
            'site',
            'programManager',
            'validator',
            'audits.leadAuditor',
            'audits.site'
        ])->findOrFail($id);

        $this->authorize('view', $program);

        return response()->json($program);
    }

    /**
     * Créer un programme
     * POST /api/v1/audit-programs
     */
    public function store(Request $request)
    {
        $this->authorize('create', AuditProgram::class);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'year' => 'required|integer|min:2020|max:2100',
            'title' => 'nullable|string|max:255',
            'program_manager_id' => 'required|exists:users,id',
            'objectives' => 'nullable|string',
            'target_processes' => 'nullable|array',
            'target_sites' => 'nullable|array',
            'risk_based_criteria' => 'nullable|array',
            'planned_audits_count' => 'nullable|integer|min:0',
        ]);

        $program = DB::transaction(function() use ($validated) {
            $program = AuditProgram::create($validated);
            
            activity('audit_program')
                ->performedOn($program)
                ->causedBy(auth()->user())
                ->log('Programme d\'audits créé');

            return $program;
        });

        return response()->json($this->loadProgramRelations($program), 201);
    }

    /**
     * Mettre à jour un programme
     * PUT /api/v1/audit-programs/{id}
     */
    public function update(Request $request, int $id)
    {
        $program = AuditProgram::findOrFail($id);
        $this->authorize('update', $program);

        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'title' => 'sometimes|string|max:255',
            'program_manager_id' => 'sometimes|exists:users,id',
            'objectives' => 'nullable|string',
            'target_processes' => 'nullable|array',
            'target_sites' => 'nullable|array',
            'risk_based_criteria' => 'nullable|array',
            'planned_audits_count' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:draft,validated,in_progress,completed,archived',
        ]);

        $program->update($validated);

        return response()->json($this->loadProgramRelations($program->fresh()));
    }

    /**
     * Supprimer un programme
     * DELETE /api/v1/audit-programs/{id}
     */
    public function destroy(int $id)
    {
        $program = AuditProgram::findOrFail($id);
        $this->authorize('delete', $program);

        // Vérifier qu'il n'y a pas d'audits réalisés
        if ($program->completed_audits_count > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un programme avec des audits réalisés'
            ], 422);
        }

        $program->delete();

        return response()->json(['message' => 'Programme supprimé'], 200);
    }

    /**
     * Valider le programme (direction)
     * POST /api/v1/audit-programs/{id}/validate
     */
    public function validate(int $id)
    {
        $program = AuditProgram::findOrFail($id);
        $this->authorize('update', $program);

        if ($program->status !== 'draft') {
            return response()->json([
                'message' => 'Seuls les programmes en brouillon peuvent être validés'
            ], 422);
        }

        $program->update([
            'status' => 'validated',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        activity('audit_program')
            ->performedOn($program)
            ->causedBy(auth()->user())
            ->log('Programme validé par la direction');

        return response()->json($this->loadProgramRelations($program->fresh()));
    }

    /**
     * Générer programme basé sur les risques
     * POST /api/v1/audit-programs/{id}/generate-from-risks
     */
    public function generateFromRisks(Request $request, int $id)
    {
        $program = AuditProgram::findOrFail($id);
        $this->authorize('update', $program);

        $validated = $request->validate([
            'min_criticality' => 'nullable|integer|min:1|max:25',
            'include_all_processes' => 'nullable|boolean',
        ]);

        $suggestions = $this->service->generateFromRiskAnalysis(
            $program,
            $validated['min_criticality'] ?? 12,
            $validated['include_all_processes'] ?? false
        );

        return response()->json([
            'message' => 'Suggestions générées',
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Export calendrier annuel XLSX
     * GET /api/v1/audit-programs/{id}/export-calendar
     */
    public function exportCalendar(int $id)
    {
        $program = AuditProgram::with('audits')->findOrFail($id);
        $this->authorize('view', $program);

        $filePath = $this->service->exportCalendarXlsx($program);

        app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => (int) $program->site_id,
            'process_id' => null,
            'process_code' => 'GEN',
            'document_kind' => 'audit_program_calendar_xlsx',
            'title' => 'Programme annuel d’audit - ' . ((int) $program->year) . ' - ' . ($program->ref ?? ('PRG-' . $program->id)),
            'description' => 'Calendrier annuel d’audit exporté en XLSX.',
            'file_source_path' => $filePath,
            'file_extension' => 'xlsx',
            'created_by' => auth()->id(),
            'type' => 'ENR',
        ]);

        return response()->download($filePath)->deleteFileAfterSend();
    }

    /**
     * Tableau de bord statistiques programme
     * GET /api/v1/audit-programs/{id}/statistics
     */
    public function statistics(int $id)
    {
        $program = AuditProgram::with('audits.findings')->findOrFail($id);
        $this->authorize('view', $program);

        $stats = [
            'completion_rate' => $program->completion_rate,
            'conformity_rate' => $program->conformity_rate,
            'nc_major_count' => $program->nc_major_count,
            'nc_minor_count' => $program->nc_minor_count,
            'observations_count' => $program->observations_count,
            'audits_by_status' => $program->audits->groupBy('status')->map->count(),
            'audits_by_quarter' => $program->audits->groupBy('quarter')->map->count(),
            'audits_by_type' => $program->audits->groupBy('type')->map->count(),
            'top_processes_audited' => $this->service->getTopProcesses($program),
            'trend_conformity' => $this->service->getConformityTrend($program),
        ];

        return response()->json($stats);
    }
}
