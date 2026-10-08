<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Site;
use App\Models\TrainingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TrainingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.competences.read')->only(['index', 'show', 'exportXlsx', 'template']);
        $this->middleware('permission:support.competences.create')->only(['store']);
        $this->middleware('permission:support.competences.update')->only(['update', 'sync']);
        $this->middleware('permission:support.competences.delete')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = TrainingPlan::query()->orderByDesc('year')->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }

        $plans = $query->get()->map(fn (TrainingPlan $plan) => $this->serializePlan($plan));

        return response()->json($plans);
    }

    public function show(Request $request, TrainingPlan $trainingPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($trainingPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        return response()->json($this->serializePlan($trainingPlan));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'status' => 'nullable|in:draft,active,closed',
            'total_budget' => 'nullable|numeric|min:0',
            'planned_formations' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        $user = $request->user();
        $enterpriseId = $user?->enterprise_id;

        if (!$enterpriseId) {
            return response()->json(['message' => 'Aucune entreprise associée à l\'utilisateur'], 422);
        }

        $siteId = $validated['site_id'] ?? $user?->site_id;
        if ($siteId) {
            $siteExists = Site::query()
                ->where('id', $siteId)
                ->where('enterprise_id', $enterpriseId)
                ->exists();
            if (!$siteExists) {
                return response()->json(['message' => 'Site invalide pour votre entreprise'], 422);
            }
        }

        $alreadyExists = TrainingPlan::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('site_id', $siteId)
            ->where('year', $validated['year'])
            ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'Un plan de formation existe déjà pour cette période'], 422);
        }

        $plan = TrainingPlan::create([
            'enterprise_id' => $enterpriseId,
            'site_id' => $siteId,
            'year' => $validated['year'],
            'status' => $validated['status'] ?? 'draft',
            'total_budget' => $validated['total_budget'] ?? null,
            'planned_formations' => $validated['planned_formations'] ?? null,
            'spent_amount' => 0,
        ]);

        return response()->json($this->serializePlan($plan->fresh()), 201);
    }

    public function update(Request $request, TrainingPlan $trainingPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($trainingPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:draft,active,closed',
            'total_budget' => 'nullable|numeric|min:0',
            'planned_formations' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if (array_key_exists('site_id', $validated) && $validated['site_id']) {
            $siteExists = Site::query()
                ->where('id', $validated['site_id'])
                ->where('enterprise_id', $request->user()?->enterprise_id)
                ->exists();
            if (!$siteExists) {
                return response()->json(['message' => 'Site invalide pour votre entreprise'], 422);
            }
        }

        $trainingPlan->update($validated);
        $this->syncPlanTotals($trainingPlan);

        return response()->json($this->serializePlan($trainingPlan->fresh()));
    }

    public function destroy(Request $request, TrainingPlan $trainingPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($trainingPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $hasFormations = $this->baseFormationQuery($trainingPlan)->exists();
        if ($hasFormations) {
            return response()->json([
                'message' => 'Suppression impossible: des formations sont rattachées à ce plan'
            ], 422);
        }

        $trainingPlan->delete();

        return response()->json(null, 204);
    }

    public function sync(Request $request, TrainingPlan $trainingPlan): JsonResponse
    {
        $ownershipError = $this->ensurePlanOwnership($trainingPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $this->syncPlanTotals($trainingPlan);

        return response()->json($this->serializePlan($trainingPlan->fresh()));
    }

    public function exportXlsx(Request $request, TrainingPlan $trainingPlan)
    {
        $ownershipError = $this->ensurePlanOwnership($trainingPlan, $request);
        if ($ownershipError) {
            return $ownershipError;
        }

        $formations = $this->baseFormationQuery($trainingPlan)
            ->orderBy('date_debut')
            ->get(['numero', 'designation', 'formateur', 'frequency', 'status', 'date_debut', 'date_fin', 'cout']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan formation');

        $sheet->setCellValue('A1', 'PLAN ANNUEL DE FORMATION');
        $sheet->setCellValue('A2', 'Année');
        $sheet->setCellValue('B2', (int) $trainingPlan->year);
        $sheet->setCellValue('D2', 'Statut');
        $sheet->setCellValue('E2', (string) $trainingPlan->status);
        $sheet->setCellValue('A3', 'Budget total');
        $sheet->setCellValue('B3', (float) ($trainingPlan->total_budget ?? 0));
        $sheet->setCellValue('D3', 'Budget consommé');
        $sheet->setCellValue('E3', (float) ($trainingPlan->spent_amount ?? 0));

        $headers = [
            'N°',
            'Désignation',
            'Formateur',
            'Récurrence',
            'Statut',
            'Début',
            'Fin',
            'Coût',
        ];
        $headerRow = 6;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $row = $headerRow + 1;
        foreach ($formations as $formation) {
            $sheet->fromArray([
                (int) $formation->numero,
                (string) $formation->designation,
                (string) ($formation->formateur ?? ''),
                (string) ($formation->frequency ?? ''),
                (string) $formation->runtimeStatus(),
                $formation->date_debut?->format('Y-m-d'),
                $formation->date_fin?->format('Y-m-d'),
                (float) ($formation->cout ?? 0),
            ], null, "A{$row}");
            $row++;
        }

        if ($row === $headerRow + 1) {
            $sheet->setCellValue("A{$row}", 'Aucune formation pour ce plan.');
            $sheet->mergeCells("A{$row}:H{$row}");
            $row++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            'plan_formation_%d_%d_%s.xlsx',
            (int) $trainingPlan->id,
            (int) $trainingPlan->year,
            now()->format('Ymd_His')
        );

        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        try {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $trainingPlan->site_id,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'training_plan_export_xlsx',
                'title' => 'Plan annuel de formation - ' . (int) $trainingPlan->year,
                'description' => 'Export XLSX du plan annuel de formation.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $request->user()?->id,
                'type' => 'ENR',
            ]);
        } catch (\Throwable) {
            // Ne bloque jamais le téléchargement de l'export.
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function template(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template plan annuel');

        $sheet->setCellValue('A1', 'PLAN ANNUEL DE FORMATION');
        $sheet->mergeCells('A1:T1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Année');
        $sheet->setCellValue('B2', now()->year);
        $sheet->setCellValue('D2', 'Version');
        $sheet->setCellValue('E2', '1.0');
        $sheet->setCellValue('G2', 'Date');
        $sheet->setCellValue('H2', now()->format('d/m/Y'));

        $headerRow = 6;
        $subHeaderRow = 7;

        $sheet->setCellValue('A' . $headerRow, 'N°');
        $sheet->setCellValue('B' . $headerRow, 'Nom de la formation');
        $sheet->setCellValue('C' . $headerRow, 'Cible(s)');
        $sheet->setCellValue('D' . $headerRow, 'Chronogramme - année');
        $sheet->setCellValue('P' . $headerRow, 'Formateur');
        $sheet->setCellValue('Q' . $headerRow, 'Coût');
        $sheet->setCellValue('R' . $headerRow, 'Date Suivi');
        $sheet->setCellValue('T' . $headerRow, 'Observations/Commentaire');

        $sheet->mergeCells("A{$headerRow}:A{$subHeaderRow}");
        $sheet->mergeCells("B{$headerRow}:B{$subHeaderRow}");
        $sheet->mergeCells("C{$headerRow}:C{$subHeaderRow}");
        $sheet->mergeCells("D{$headerRow}:O{$headerRow}");
        $sheet->mergeCells("P{$headerRow}:P{$subHeaderRow}");
        $sheet->mergeCells("Q{$headerRow}:Q{$subHeaderRow}");
        $sheet->mergeCells("R{$headerRow}:S{$headerRow}");
        $sheet->mergeCells("T{$headerRow}:T{$subHeaderRow}");

        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
        foreach ($months as $index => $label) {
            $column = chr(ord('D') + $index);
            $sheet->setCellValue($column . $subHeaderRow, $label);
        }
        $sheet->setCellValue('R' . $subHeaderRow, 'Début');
        $sheet->setCellValue('S' . $subHeaderRow, 'Fin');

        $sheet->getStyle("A{$headerRow}:T{$subHeaderRow}")->getFont()->setBold(true);
        $sheet->freezePane('A8');

        foreach (range('A', 'T') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf('template_plan_annuel_%s.xlsx', now()->format('Ymd_His'));
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $user = $request->user();
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
                'document_kind' => 'training_plan_template_xlsx',
                'title' => 'Modèle Plan annuel de formation',
                'description' => 'Modèle XLSX de plan annuel de formation.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'FOR',
            ]);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function syncPlanTotals(TrainingPlan $plan): void
    {
        $formations = $this->baseFormationQuery($plan)->get(['status', 'cout']);
        $spent = $formations
            ->filter(fn (Formation $formation) => $formation->status === Formation::STATUS_REALISEE)
            ->sum('cout');

        $plan->update(['spent_amount' => $spent ?: 0]);
    }

    private function serializePlan(TrainingPlan $plan): array
    {
        $formations = $this->baseFormationQuery($plan)->get(['id', 'status', 'cout', 'date_debut', 'date_fin']);

        $realisees = $formations->filter(fn (Formation $formation) => $formation->status === Formation::STATUS_REALISEE)->count();
        $annulees = $formations->filter(fn (Formation $formation) => $formation->status === Formation::STATUS_ANNULEE)->count();
        $enAttente = $formations->filter(fn (Formation $formation) => $formation->runtimeStatus() === Formation::STATUS_EN_ATTENTE)->count();
        $planifiees = $formations->filter(fn (Formation $formation) => $formation->runtimeStatus() === Formation::STATUS_PLANIFIEE)->count();
        $replanifiees = $formations->filter(fn (Formation $formation) => $formation->runtimeStatus() === Formation::STATUS_REPLANIFIEE)->count();
        $budgetRealise = $formations
            ->filter(fn (Formation $formation) => $formation->status === Formation::STATUS_REALISEE)
            ->sum('cout');
        $budgetEngage = $formations
            ->filter(fn (Formation $formation) => $formation->status !== Formation::STATUS_ANNULEE)
            ->sum('cout');
        $budgetTotal = $plan->total_budget !== null ? (float) $plan->total_budget : (float) $formations->sum('cout');
        $budgetRestant = max(0, $budgetTotal - $budgetEngage);

        return [
            'id' => $plan->id,
            'enterprise_id' => $plan->enterprise_id,
            'site_id' => $plan->site_id,
            'year' => (int) $plan->year,
            'status' => $plan->status,
            'total_budget' => $budgetTotal,
            'planned_formations' => $plan->planned_formations,
            'spent_amount' => (float) $budgetRealise,
            'budget_engaged' => (float) $budgetEngage,
            'budget_realized' => (float) $budgetRealise,
            'budget_remaining' => (float) $budgetRestant,
            'stats' => [
                'total_formations' => $formations->count(),
                'planifiees' => $planifiees,
                'replanifiees' => $replanifiees,
                'en_attente' => $enAttente,
                'realisees' => $realisees,
                'annulees' => $annulees,
                'taux_realisation' => $formations->count() > 0 ? round(($realisees / $formations->count()) * 100, 1) : 0,
            ],
            'created_at' => $plan->created_at?->toISOString(),
            'updated_at' => $plan->updated_at?->toISOString(),
        ];
    }

    private function baseFormationQuery(TrainingPlan $plan)
    {
        return Formation::query()
            ->where('enterprise_id', $plan->enterprise_id)
            ->when($plan->site_id !== null, fn ($query) => $query->where('site_id', $plan->site_id))
            ->where(function ($query) use ($plan) {
                $query->whereYear('date_debut', $plan->year)
                    ->orWhere(function ($nested) use ($plan) {
                        $nested->whereNull('date_debut')->where('plan_year', $plan->year);
                    });
            });
    }

    private function ensurePlanOwnership(TrainingPlan $plan, Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->enterprise_id) {
            return response()->json(['message' => 'Utilisateur non associé à une entreprise'], 422);
        }

        if ((int) $plan->enterprise_id !== (int) $user->enterprise_id) {
            return response()->json(['message' => 'Accès non autorisé à ce plan de formation'], 403);
        }

        return null;
    }
}
