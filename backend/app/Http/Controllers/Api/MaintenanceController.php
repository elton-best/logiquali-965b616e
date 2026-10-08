<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipement;
use App\Models\EquipementCodeAlias;
use App\Models\Maintenance;
use App\Models\MaintenanceSuivi;
use App\Services\DocumentBrandingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.ressources.read')->only(['index', 'show', 'alertes', 'template']);
        $this->middleware('permission:support.ressources.manage_maintenance')->only(['store', 'import']);
        $this->middleware('permission:support.ressources.manage_maintenance')->only(['update', 'suivre']);
        $this->middleware('permission:support.ressources.manage_maintenance')->only(['destroy']);
        $this->middleware('permission:support.ressources.manage_maintenance')->only(['export']);
    }

    public function index(Request $request)
    {
        $query = Maintenance::with(['equipement.categorie', 'equipement.localisation']);

        if ($request->has('equipement_id')) {
            $query->where('equipement_id', $request->equipement_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('avec_alertes') && $request->avec_alertes) {
            $query->avecAlertes();
        }

        $maintenances = $query->orderBy('date_prevue')->get();

        return response()->json($maintenances->map(function ($maintenance) {
            return [
                ...$maintenance->toArray(),
                'niveau_alerte' => $maintenance->niveau_alerte,
                'suivi_active' => $maintenance->suivi_active,
                'alert_state' => $this->resolveAlertState($maintenance),
            ];
        }));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipement_id' => 'required|exists:equipements,id',
            'type' => 'required|in:preventive,corrective,etalonnage',
            'date_prevue' => 'required|date',
            'description' => 'nullable|string',
            'responsable' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenance = Maintenance::create($request->all());

        return response()->json($maintenance->load(['equipement']), 201);
    }

    public function show($id)
    {
        $maintenance = Maintenance::with(['equipement', 'suivis.user'])->findOrFail($id);
        
        return response()->json([
            ...$maintenance->toArray(),
            'niveau_alerte' => $maintenance->niveau_alerte,
            'suivi_active' => $maintenance->suivi_active,
            'alert_state' => $this->resolveAlertState($maintenance),
        ]);
    }

    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:preventive,corrective,etalonnage',
            'date_prevue' => 'sometimes|date',
            'statut' => 'sometimes|in:planifie,en_cours,realise,reporte,annule',
            'description' => 'nullable|string',
            'responsable' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maintenance->update($request->all());

        return response()->json($maintenance->load(['equipement']));
    }

    public function destroy($id)
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance supprimée avec succès']);
    }

    public function suivre(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:realise,reporte',
            'date_action' => 'required|date',
            'nouvelle_date_prevue' => 'required_if:action,reporte|nullable|date|after:date_action',
            'commentaire' => 'nullable|string',
            'preuve' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $preuvePath = null;
        if ($request->hasFile('preuve')) {
            $preuvePath = $request->file('preuve')->store('maintenances/preuves', 'public');
        }

        $currentUser = $request->user();
        $suivi = MaintenanceSuivi::create([
            'maintenance_id' => $maintenance->id,
            'user_id' => (int) ($currentUser?->id ?? 0),
            'action' => $request->action,
            'date_action' => $request->date_action,
            'nouvelle_date_prevue' => $request->nouvelle_date_prevue,
            'commentaire' => $request->commentaire,
            'preuve_path' => $preuvePath,
        ]);

        if ($request->action === 'realise') {
            $maintenance->update([
                'statut' => 'realise',
                'date_realisee' => $request->date_action,
            ]);
        } else {
            $maintenance->update([
                'statut' => 'reporte',
                'date_prevue' => $request->nouvelle_date_prevue,
            ]);
        }

        return response()->json([
            'suivi' => $suivi->load(['user']),
            'maintenance' => $maintenance->fresh()->load(['equipement']),
        ]);
    }

    public function alertes()
    {
        $maintenances = Maintenance::with(['equipement.categorie', 'equipement.localisation'])
            ->avecAlertes()
            ->get();

        return response()->json($maintenances->map(function ($maintenance) {
            return [
                ...$maintenance->toArray(),
                'niveau_alerte' => $maintenance->niveau_alerte,
                'suivi_active' => $maintenance->suivi_active,
                'alert_state' => $this->resolveAlertState($maintenance),
            ];
        }));
    }

    private function resolveAlertState(Maintenance $maintenance): string
    {
        if (in_array($maintenance->statut, ['realise', 'annule'], true)) {
            return 'resolved';
        }

        if ($maintenance->statut === 'planifie' && $maintenance->date_prevue && $maintenance->date_prevue->lt(Carbon::now()->startOfDay())) {
            return 'expired';
        }

        return 'active';
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(\PhpOffice\PhpSpreadsheet\IOFactory::identify($file));
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $user = $request->user();
            $enterpriseId = (int) ($user?->enterprise_id ?? 0);
            if ($enterpriseId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise introuvable pour cet utilisateur.',
                    'summary' => [
                        'total' => 0,
                        'created' => 0,
                        'failed' => 0,
                    ],
                    'preview' => [],
                    'errors' => [],
                    'report' => [],
                ], 422);
            }

            $headers = array_map(
                fn ($header) => $this->normalizeHeader((string) $header),
                $rows[0]
            );
            $headerIndex = $this->buildMaintenanceHeaderIndex($headers);

            if (!isset($headerIndex['equipement'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Colonne obligatoire manquante: equipement',
                    'summary' => [
                        'total' => 0,
                        'created' => 0,
                        'failed' => 0,
                    ],
                    'preview' => [],
                    'errors' => ['Colonne equipement introuvable'],
                    'report' => [],
                ], 422);
            }

            $equipements = Equipement::query()
                ->where('enterprise_id', $enterpriseId)
                ->get();
            $equipementsByCode = $equipements->keyBy(fn ($item) => strtoupper((string) $item->code_complet));
            $equipementsByName = $equipements->keyBy(fn ($item) => mb_strtoupper(trim((string) $item->nom_commun)));

            $imported = 0;
            $failed = 0;
            $errors = [];
            $preview = [];
            $report = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                $rowNumber = $index + 2;
                if ($this->isEmptyImportRow($row)) {
                    continue;
                }

                $rowData = $this->extractMaintenanceImportRowData($row, $headerIndex);

                try {
                    $equipement = $this->resolveEquipementForImport(
                        $rowData['equipement'],
                        $enterpriseId,
                        $equipementsByCode,
                        $equipementsByName
                    );
                    if (!$equipement) {
                        throw new \InvalidArgumentException('Équipement non trouvé');
                    }

                    if (!$user?->isEnterpriseAdmin() && (int) ($user?->site_id ?? 0) > 0 && (int) $equipement->site_id !== (int) $user->site_id) {
                        throw new \InvalidArgumentException('Équipement non autorisé pour cet utilisateur');
                    }

                    $type = $this->normalizeMaintenanceType($rowData['type']);
                    $statut = $this->normalizeMaintenanceStatus($rowData['statut']);
                    $datePrevue = $this->normalizeMaintenanceDate($rowData['date_prevue']);
                    if ($datePrevue === null) {
                        throw new \InvalidArgumentException('Date prévue invalide');
                    }

                    $maintenance = Maintenance::create([
                        'enterprise_id' => $enterpriseId,
                        'equipement_id' => $equipement->id,
                        'type' => $type,
                        'date_prevue' => $datePrevue,
                        'responsable' => $rowData['responsable'] !== '' ? $rowData['responsable'] : null,
                        'description' => $rowData['description'] !== '' ? $rowData['description'] : null,
                        'statut' => $statut,
                    ]);

                    $imported++;
                    if (count($preview) < 5) {
                        $preview[] = [
                            'equipement' => $equipement->nom_commun,
                            'type' => $type,
                            'date_prevue' => $maintenance->date_prevue,
                        ];
                    }

                    $report[] = [
                        'row' => $rowNumber,
                        'status' => 'created',
                        'maintenance_id' => $maintenance->id,
                        'equipement' => $equipement->nom_commun,
                        'message' => 'Maintenance créée',
                    ];
                } catch (\Throwable $lineError) {
                    $failed++;
                    $errorMessage = "Ligne {$rowNumber}: {$lineError->getMessage()}";
                    $errors[] = $errorMessage;
                    $report[] = [
                        'row' => $rowNumber,
                        'status' => 'failed',
                        'message' => $errorMessage,
                    ];
                }
            }

            $total = $imported + $failed;

            return response()->json([
                'success' => true,
                'message' => "{$imported} maintenance(s) importée(s), {$failed} en échec",
                'summary' => [
                    'total' => $total,
                    'created' => $imported,
                    'failed' => $failed,
                ],
                'preview' => $preview,
                'errors' => $errors,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'importation: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        return trim($normalized, '_');
    }

    private function buildMaintenanceHeaderIndex(array $headers): array
    {
        $aliases = [
            'equipement' => ['equipement', 'code_equipement', 'equipment', 'equipment_code', 'code'],
            'type' => ['type', 'type_maintenance', 'maintenance_type'],
            'date_prevue' => ['date_prevue', 'date', 'planned_date', 'date_planifiee'],
            'responsable' => ['responsable', 'responsible'],
            'description' => ['description', 'details', 'commentaire', 'comment'],
            'statut' => ['statut', 'status'],
        ];

        $index = [];
        foreach ($aliases as $target => $values) {
            foreach ($values as $alias) {
                $position = array_search($alias, $headers, true);
                if ($position !== false) {
                    $index[$target] = $position;
                    break;
                }
            }
        }

        return $index;
    }

    private function extractMaintenanceImportRowData(array $row, array $headerIndex): array
    {
        $extract = fn (string $key): string => isset($headerIndex[$key]) ? trim((string) ($row[$headerIndex[$key]] ?? '')) : '';

        return [
            'equipement' => $extract('equipement'),
            'type' => $extract('type'),
            'date_prevue' => $extract('date_prevue'),
            'responsable' => $extract('responsable'),
            'description' => $extract('description'),
            'statut' => $extract('statut'),
        ];
    }

    private function resolveEquipementForImport(string $value, int $enterpriseId, $equipementsByCode, $equipementsByName): ?Equipement
    {
        $needle = trim($value);
        if ($needle === '') {
            return null;
        }

        $byCode = $equipementsByCode->get(strtoupper($needle));
        if ($byCode) {
            return $byCode;
        }

        $alias = EquipementCodeAlias::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('code_alias', strtoupper($needle))
            ->latest('id')
            ->first();
        if ($alias) {
            return Equipement::query()
                ->where('enterprise_id', $enterpriseId)
                ->where('id', $alias->equipement_id)
                ->first();
        }

        return $equipementsByName->get(mb_strtoupper($needle));
    }

    private function normalizeMaintenanceType(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $mapping = [
            '' => 'preventive',
            'preventive' => 'preventive',
            'préventive' => 'preventive',
            'preventif' => 'preventive',
            'préventif' => 'preventive',
            'corrective' => 'corrective',
            'correctif' => 'corrective',
            'etalonnage' => 'etalonnage',
            'étalonnage' => 'etalonnage',
        ];

        return $mapping[$normalized] ?? 'preventive';
    }

    private function normalizeMaintenanceStatus(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $mapping = [
            '' => 'planifie',
            'planifie' => 'planifie',
            'planifié' => 'planifie',
            'en_cours' => 'en_cours',
            'en cours' => 'en_cours',
            'realise' => 'realise',
            'réalisé' => 'realise',
            'reporte' => 'reporte',
            'reporté' => 'reporte',
            'annule' => 'annule',
            'annulé' => 'annule',
        ];

        return $mapping[$normalized] ?? 'planifie';
    }

    private function normalizeMaintenanceDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return now()->addDays(30)->toDateString();
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isEmptyImportRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    public function export(Request $request)
    {
        $maintenances = Maintenance::with(['equipement.categorie', 'equipement.localisation'])
            ->orderBy('date_prevue')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Code Équipement');
        $sheet->setCellValue('B1', 'Nom Équipement');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Date Prévue');
        $sheet->setCellValue('E1', 'Statut');
        $sheet->setCellValue('F1', 'Responsable');
        $sheet->setCellValue('G1', 'Description');
        $sheet->setCellValue('H1', 'Alerte');

        $row = 2;
        foreach ($maintenances as $maintenance) {
            $sheet->setCellValue('A' . $row, $maintenance->equipement->code_complet);
            $sheet->setCellValue('B' . $row, $maintenance->equipement->nom_commun);
            $sheet->setCellValue('C' . $row, ucfirst($maintenance->type));
            $sheet->setCellValue('D' . $row, $maintenance->date_prevue);
            $sheet->setCellValue('E' . $row, ucfirst($maintenance->statut));
            $sheet->setCellValue('F' . $row, $maintenance->responsable);
            $sheet->setCellValue('G' . $row, $maintenance->description);
            $sheet->setCellValue('H' . $row, $maintenance->niveau_alerte ? strtoupper($maintenance->niveau_alerte) : '');
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $enterprise = $request->user()?->enterprise;
        if ($enterprise) {
            app(DocumentBrandingService::class)->applyXlsxBranding(
                $spreadsheet,
                $enterprise,
                'Plan de maintenance'
            );
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'plan_maintenance_' . date('Y-m-d') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

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
                'document_kind' => 'maintenance_plan_xlsx',
                'title' => 'Plan de maintenance',
                'description' => 'Export XLSX du plan de maintenance.',
                'file_source_path' => $temp_file,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'ENR',
            ]);
        }

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    public function template(Request $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'equipement');
        $sheet->setCellValue('B1', 'type');
        $sheet->setCellValue('C1', 'date_prevue');
        $sheet->setCellValue('D1', 'responsable');
        $sheet->setCellValue('E1', 'description');
        $sheet->setCellValue('F1', 'statut');

        $sheet->setCellValue('A2', 'BEG/INF/ECR/CEO/001/2025');
        $sheet->setCellValue('B2', 'preventive');
        $sheet->setCellValue('C2', now()->addDays(15)->toDateString());
        $sheet->setCellValue('D2', 'Responsable Maintenance');
        $sheet->setCellValue('E2', 'Contrôle trimestriel');
        $sheet->setCellValue('F2', 'planifie');

        $sheet->setCellValue('A3', 'BEG/INF/IMP/CEO/002/2025');
        $sheet->setCellValue('B3', 'etalonnage');
        $sheet->setCellValue('C3', now()->addDays(30)->toDateString());
        $sheet->setCellValue('D3', 'Technicien Métrologie');
        $sheet->setCellValue('E3', 'Étalonnage annuel');
        $sheet->setCellValue('F3', 'planifie');

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $enterprise = $request->user()?->enterprise;
        if ($enterprise) {
            app(DocumentBrandingService::class)->applyXlsxBranding(
                $spreadsheet,
                $enterprise,
                'Modèle import maintenances'
            );
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'modele_import_maintenances.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'maint_template_');
        $writer->save($tempFile);

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
                'document_kind' => 'maintenance_import_template_xlsx',
                'title' => 'Modèle import maintenances',
                'description' => 'Modèle XLSX pour import des maintenances.',
                'file_source_path' => $tempFile,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'FOR',
            ]);
        }

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
