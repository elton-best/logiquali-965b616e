<?php

namespace App\Modules\Support\Controllers;

use App\Models\Modification;

use App\Http\Controllers\Controller;
use App\Models\Equipement;
use App\Models\EquipementCodeAlias;
use App\Models\CodificationElement;
use App\Models\Maintenance;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EquipementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.ressources.read')->only(['index', 'show']);
        $this->middleware('permission:support.ressources.create')->only(['store']);
        $this->middleware('permission:support.ressources.update')->only(['update']);
        $this->middleware('permission:support.ressources.delete')->only(['destroy']);
        $this->middleware('permission:support.ressources.manage_maintenance')->only(['prochainIndice', 'import']);
    }

    public function index(Request $request)
    {
        $query = Equipement::with(['categorie', 'localisation']);

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->has('localisation_id')) {
            $query->where('localisation_id', $request->localisation_id);
        }

        if ($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->actif);
        }

        return response()->json($query->orderBy('code_complet')->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        if ($enterpriseId <= 0) {
            \Illuminate\Support\Facades\Log::warning('Equipment store rejected: no enterprise for user', [
                'user_id' => (int) ($user?->id ?? 0),
                'user_type' => (string) ($user?->user_type ?? 'unknown'),
            ]);
            return response()->json([
                'message' => 'Paramètres invalides.',
            ], 422);
        }

        $enterprise = $user?->enterprise;
        $codificationMode = (string) ($enterprise?->codification_mode ?? 'standard');
        $enterpriseSigle = (string) ($enterprise?->sigle
            ?: Equipement::deriveEnterpriseSigle((string) ($enterprise?->name ?? $user?->enterprise?->name ?? 'ENT')));

        $validator = Validator::make($request->all(), [
            'categorie_id' => 'required|exists:codification_elements,id',
            'localisation_id' => 'required|exists:codification_elements,id',
            'nom_commun' => 'required|string|max:255',
            'annee_acquisition' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'marque' => 'nullable|string|max:255',
            'modele' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'etat' => 'required|in:tres_bon,bon,mauvais',
            'valeur_acquisition' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
            'necessite_maintenance' => 'boolean',
            'frequence_maintenance_jours' => 'nullable|integer|min:1',
            'code_complet' => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categorie = CodificationElement::findOrFail($request->categorie_id);
        $localisation = CodificationElement::findOrFail($request->localisation_id);

        if ($categorie->type !== 'categorie' || $localisation->type !== 'localisation') {
            return response()->json([
                'message' => 'Catégorie ou localisation invalide.',
            ], 422);
        }

        if ((int) $categorie->enterprise_id !== $enterpriseId || (int) $localisation->enterprise_id !== $enterpriseId) {
            return response()->json([
                'message' => 'Catégorie/localisation non autorisée pour cette entreprise.',
            ], 422);
        }

        $nomCommunAbrege = strtoupper(substr((string) $request->nom_commun, 0, 3));
        $providedCode = trim((string) $request->input('code_complet', ''));
        if ($codificationMode === 'custom') {
            if ($providedCode === '') {
                return response()->json([
                    'message' => 'Le code équipement est obligatoire en mode personnalisé.',
                ], 422);
            }

            $exists = Equipement::query()
                ->where('enterprise_id', $enterpriseId)
                ->where('code_complet', $providedCode)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Ce code équipement est déjà utilisé dans l’entreprise.',
                ], 422);
            }
        }

        $indice = Equipement::prochainIndice($nomCommunAbrege, $enterpriseId);
        $generatedCode = Equipement::genererCodeComplet(
            $enterpriseSigle,
            $categorie->code,
            $nomCommunAbrege,
            $localisation->code,
            $indice,
            $request->annee_acquisition
        );
        $codeComplet = $codificationMode === 'custom' ? $providedCode : $generatedCode;

        $equipement = Equipement::create([
            ...$request->all(),
            'enterprise_id' => $enterpriseId,
            'code_complet' => $codeComplet,
            'nom_commun_abrege' => $nomCommunAbrege,
            'indice' => $indice,
        ]);

        $this->recordInitialCodeHistory(
            $equipement,
            $enterpriseId,
            $user?->id ?? null,
            $codificationMode === 'custom' ? 'initial_custom_code' : 'initial',
            $codificationMode === 'custom' ? 'Code principal fourni manuellement' : 'Création équipement'
        );

        if ($equipement->necessite_maintenance && $equipement->frequence_maintenance_jours) {
            $this->scheduleInitialMaintenance(
                $equipement,
                (int) $equipement->frequence_maintenance_jours,
                $enterpriseId
            );
        }

        return response()->json($equipement->load(['categorie', 'localisation']), 201);
    }

    public function show($id)
    {
        $equipement = Equipement::with(['categorie', 'localisation', 'maintenances', 'codeAliases'])->findOrFail($id);
        return response()->json($equipement);
    }

    public function update(Request $request, $id)
    {
        $equipement = Equipement::with(['categorie', 'localisation', 'codeAliases'])->findOrFail($id);
        $user = $request->user();
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        if ($enterpriseId <= 0 || (int) $equipement->enterprise_id !== $enterpriseId) {
            \Illuminate\Support\Facades\Log::warning('Equipment update rejected: enterprise mismatch', [
                'user_id' => (int) ($user?->id ?? 0),
                'user_enterprise_id' => $enterpriseId,
                'equipement_id' => (int) $equipement->id,
                'equipement_enterprise_id' => (int) $equipement->enterprise_id,
            ]);
            return response()->json([
                'message' => 'Paramètres invalides.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'site_id' => 'sometimes|integer|exists:sites,id',
            'categorie_id' => 'sometimes|integer|exists:codification_elements,id',
            'localisation_id' => 'sometimes|integer|exists:codification_elements,id',
            'nom_commun' => 'sometimes|string|max:255',
            'annee_acquisition' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'marque' => 'nullable|string|max:255',
            'modele' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'etat' => 'sometimes|in:tres_bon,bon,mauvais',
            'valeur_acquisition' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
            'necessite_maintenance' => 'boolean',
            'frequence_maintenance_jours' => 'nullable|integer|min:1',
            'actif' => 'boolean',
            'code_complet' => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $enterprise = $user?->enterprise;
        $codificationMode = (string) ($enterprise?->codification_mode ?? 'standard');
        $enterpriseSigle = (string) ($enterprise?->sigle
            ?: Equipement::deriveEnterpriseSigle((string) ($enterprise?->name ?? $user?->enterprise?->name ?? 'ENT')));
        if ($codificationMode !== 'custom') {
            unset($validated['code_complet']);
        }

        try {
            DB::transaction(function () use ($equipement, $validated, $enterpriseId, $enterpriseSigle, $user, $codificationMode): void {
                $this->assertScopedReferences($validated, $enterpriseId, $user);
                if ($codificationMode === 'custom') {
                    $payload = $this->applyCustomCodeUpdate(
                        $equipement,
                        $validated,
                        $enterpriseId,
                        $user?->id ?? null
                    );
                    $equipement->update($payload);
                    return;
                }

                $payload = $this->buildPayloadWithPotentialRecodification(
                    $equipement,
                    $validated,
                    $enterpriseId,
                    $enterpriseSigle,
                    $user?->id ?? null
                );
                $equipement->update($payload);
            });
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $equipement = $equipement->fresh();
        if ($equipement->necessite_maintenance && $equipement->frequence_maintenance_jours) {
            $this->scheduleInitialMaintenance(
                $equipement,
                (int) $equipement->frequence_maintenance_jours,
                $enterpriseId
            );
        }

        return response()->json($equipement->fresh(['categorie', 'localisation', 'codeAliases']));
    }

    public function destroy($id)
    {
        $equipement = Equipement::findOrFail($id);
        $equipement->delete();

        return response()->json(['message' => 'Équipement supprimé avec succès']);
    }

    public function prochainIndice(Request $request)
    {
        $enterpriseId = (int) ($request->user()?->enterprise_id ?? 0);
        if ($enterpriseId <= 0) {
            \Illuminate\Support\Facades\Log::warning('Equipment prochainIndice rejected: no enterprise', [
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);
            return response()->json([
                'message' => 'Paramètres invalides.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nom_commun' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $nomCommunAbrege = strtoupper(substr((string) $request->nom_commun, 0, 3));
        $indice = Equipement::prochainIndice($nomCommunAbrege, $enterpriseId);

        return response()->json([
            'nom_commun_abrege' => $nomCommunAbrege,
            'indice' => $indice,
        ]);
    }

    private function scheduleInitialMaintenance(Equipement $equipement, int $frequencyDays, int $enterpriseId): void
    {
        if ($frequencyDays <= 0) {
            return;
        }

        $hasFuture = Maintenance::query()
            ->where('equipement_id', $equipement->id)
            ->whereIn('statut', ['planifie', 'en_cours', 'reporte'])
            ->exists();

        if ($hasFuture) {
            return;
        }

        Maintenance::create([
            'enterprise_id' => $enterpriseId,
            'equipement_id' => $equipement->id,
            'type' => 'preventive',
            'date_prevue' => Carbon::now()->addDays($frequencyDays)->toDateString(),
            'statut' => 'planifie',
            'description' => 'Maintenance préventive initiale',
        ]);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        if ($enterpriseId <= 0) {
            \Illuminate\Support\Facades\Log::warning('Equipment import rejected: no enterprise', [
                'user_id' => (int) ($user?->id ?? 0),
            ]);
            return response()->json([
                'message' => 'Paramètres invalides.',
            ], 422);
        }

        $enterprise = $user?->enterprise;
        $codificationMode = (string) ($enterprise?->codification_mode ?? 'standard');
        $enterpriseSigle = (string) ($enterprise?->sigle
            ?: Equipement::deriveEnterpriseSigle((string) ($enterprise?->name ?? $user?->enterprise?->name ?? 'ENT')));

        try {
            $file = $request->file('file');
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($file)
            );
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (count($rows) <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier est vide ou ne contient pas de données exploitables.',
                    'summary' => [
                        'total' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'failed' => 0,
                    ],
                    'report' => [],
                ], 422);
            }

            $headers = array_map(
                fn ($header) => $this->normalizeHeader((string) $header),
                $rows[0]
            );
            $headerIndex = $this->buildHeaderIndex($headers);

            $requiredHeaders = ['site', 'nom', 'categorie', 'localisation', 'etat', 'annee'];
            $missingHeaders = array_values(array_filter($requiredHeaders, fn ($required) => !isset($headerIndex[$required])));
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Colonnes obligatoires manquantes: ' . implode(', ', $missingHeaders),
                    'summary' => [
                        'total' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'failed' => 0,
                    ],
                    'report' => [],
                ], 422);
            }

            $sites = Site::query()
                ->where('enterprise_id', $enterpriseId)
                ->get()
                ->keyBy('id');

            $codifications = CodificationElement::query()
                ->where('enterprise_id', $enterpriseId)
                ->whereIn('type', ['categorie', 'localisation'])
                ->get();

            $categoriesByCode = $codifications->where('type', 'categorie')->keyBy(fn ($item) => strtoupper($item->code));
            $categoriesByLabel = $codifications->where('type', 'categorie')->keyBy(fn ($item) => mb_strtoupper(trim($item->libelle)));
            $localisationsByCode = $codifications->where('type', 'localisation')->keyBy(fn ($item) => strtoupper($item->code));
            $localisationsByLabel = $codifications->where('type', 'localisation')->keyBy(fn ($item) => mb_strtoupper(trim($item->libelle)));

            $report = [];
            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach (array_slice($rows, 1) as $index => $row) {
                $rowNumber = $index + 2;
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rowData = $this->extractImportRowData($row, $headerIndex);

                $lineErrors = [];
                $site = $this->resolveSite($sites, $rowData['site']);
                if (!$site) {
                    \Illuminate\Support\Facades\Log::debug('Equipment import: site not found', [
                        'row' => $rowNumber,
                        'site_input' => $rowData['site'],
                    ]);
                    $lineErrors[] = 'Paramètres invalides';
                }

                if (!$user?->isEnterpriseAdmin() && (int) ($user?->site_id ?? 0) > 0 && (int) ($site?->id ?? 0) !== (int) $user->site_id) {
                    $lineErrors[] = 'Site non autorisé pour cet utilisateur';
                }

                $category = $this->resolveCodification($rowData['categorie'], $categoriesByCode, $categoriesByLabel);
                if (!$category) {
                    \Illuminate\Support\Facades\Log::debug('Equipment import: category not found', [
                        'row' => $rowNumber,
                        'category_input' => $rowData['categorie'],
                    ]);
                    $lineErrors[] = 'Paramètres invalides';
                }

                $localisation = $this->resolveCodification($rowData['localisation'], $localisationsByCode, $localisationsByLabel);
                if (!$localisation) {
                    \Illuminate\Support\Facades\Log::debug('Equipment import: localisation not found', [
                        'row' => $rowNumber,
                        'localisation_input' => $rowData['localisation'],
                    ]);
                    $lineErrors[] = 'Paramètres invalides';
                }

                $etat = $this->normalizeEtat($rowData['etat']);
                if (!$etat) {
                    $lineErrors[] = 'État invalide';
                }

                $annee = (int) $rowData['annee'];
                if ($annee < 1900 || $annee > (int) date('Y') + 1) {
                    $lineErrors[] = 'Année invalide';
                }

                if (trim($rowData['nom']) === '') {
                    $lineErrors[] = 'Nom commun obligatoire';
                }

                if (!empty($lineErrors)) {
                    $failed++;
                    $report[] = [
                        'row' => $rowNumber,
                        'status' => 'failed',
                        'message' => implode(' | ', $lineErrors),
                    ];
                    continue;
                }

                $nomCommunAbrege = strtoupper(substr((string) $rowData['nom'], 0, 3));
                $providedCode = trim((string) $rowData['code_complet']);

                $equipement = null;
                if ($providedCode !== '') {
                    $equipement = Equipement::query()
                        ->where('enterprise_id', $enterpriseId)
                        ->where('code_complet', $providedCode)
                        ->first();
                    if (!$equipement) {
                        $alias = EquipementCodeAlias::query()
                            ->where('enterprise_id', $enterpriseId)
                            ->where('code_alias', $providedCode)
                            ->latest('id')
                            ->first();
                        if ($alias) {
                            $equipement = Equipement::query()
                                ->where('enterprise_id', $enterpriseId)
                                ->where('id', $alias->equipement_id)
                                ->first();
                        }
                    }
                }

                $payload = [
                    'enterprise_id' => $enterpriseId,
                    'site_id' => (int) $site->id,
                    'categorie_id' => (int) $category->id,
                    'localisation_id' => (int) $localisation->id,
                    'nom_commun' => trim((string) $rowData['nom']),
                    'nom_commun_abrege' => $nomCommunAbrege,
                    'annee_acquisition' => $annee,
                    'etat' => $etat,
                    'marque' => $rowData['marque'] !== '' ? $rowData['marque'] : null,
                    'modele' => $rowData['modele'] !== '' ? $rowData['modele'] : null,
                    'numero_serie' => $rowData['numero_serie'] !== '' ? $rowData['numero_serie'] : null,
                    'valeur_acquisition' => is_numeric($rowData['valeur']) ? (float) $rowData['valeur'] : null,
                    'observations' => $rowData['observations'] !== '' ? $rowData['observations'] : null,
                    'necessite_maintenance' => $this->normalizeBoolean($rowData['necessite_maintenance']),
                    'frequence_maintenance_jours' => is_numeric($rowData['frequence']) ? (int) $rowData['frequence'] : null,
                    'actif' => true,
                ];

                if ($codificationMode === 'custom' && $providedCode !== '') {
                    $payload['code_complet'] = $providedCode;
                }

                try {
                    if ($codificationMode === 'custom' && $providedCode === '') {
                        $failed++;
                        $report[] = [
                            'row' => $rowNumber,
                            'status' => 'failed',
                            'message' => 'Code équipement obligatoire en mode personnalisé',
                        ];
                        continue;
                    }

                    if ($equipement) {
                        if ($codificationMode === 'custom') {
                            $payload = $this->applyCustomCodeUpdate(
                                $equipement,
                                $payload,
                                $enterpriseId,
                                $user?->id ?? null
                            );
                            $equipement->update($payload);
                        } else {
                            $payload = $this->buildPayloadWithPotentialRecodification(
                                $equipement,
                                $payload,
                                $enterpriseId,
                                $enterpriseSigle,
                                $user?->id ?? null
                            );
                            $equipement->update($payload);
                        }
                        $updated++;
                        $report[] = [
                            'row' => $rowNumber,
                            'status' => 'updated',
                            'equipement_id' => $equipement->id,
                            'code_complet' => $equipement->code_complet,
                            'message' => 'Équipement mis à jour',
                        ];
                        continue;
                    }

                    $indice = Equipement::prochainIndice($nomCommunAbrege, $enterpriseId);
                    $generatedCode = Equipement::genererCodeComplet(
                        $enterpriseSigle,
                        (string) $category->code,
                        $nomCommunAbrege,
                        (string) $localisation->code,
                        $indice,
                        $annee
                    );
                    $codeComplet = $codificationMode === 'custom' ? $providedCode : $generatedCode;

                    $payload['indice'] = $indice;
                    $payload['code_complet'] = $codeComplet;

                    $createdEquipement = Equipement::create($payload);
                    $this->recordInitialCodeHistory(
                        $createdEquipement,
                        $enterpriseId,
                        $user?->id ?? null,
                        $codificationMode === 'custom' ? 'initial_custom_code' : 'initial',
                        $codificationMode === 'custom' ? 'Code principal fourni via import' : 'Création équipement'
                    );
                    $created++;
                    $report[] = [
                        'row' => $rowNumber,
                        'status' => 'created',
                        'equipement_id' => $createdEquipement->id,
                        'code_complet' => $createdEquipement->code_complet,
                        'message' => 'Équipement créé',
                    ];
                } catch (\Throwable $exception) {
                    $failed++;
                    $report[] = [
                        'row' => $rowNumber,
                        'status' => 'failed',
                        'message' => 'Erreur lors du traitement de la ligne: ' . $exception->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import inventaire terminé',
                'summary' => [
                    'total' => $created + $updated + $failed,
                    'created' => $created,
                    'updated' => $updated,
                    'failed' => $failed,
                ],
                'report' => $report,
            ]);
        } catch (\Throwable $e) {
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

    private function buildHeaderIndex(array $headers): array
    {
        $aliases = [
            'site' => ['site', 'site_id', 'site_nom', 'site_name', 'nom_site'],
            'code_complet' => ['code_complet', 'code', 'equipment_code'],
            'nom' => ['nom', 'nom_commun', 'name', 'designation'],
            'categorie' => ['categorie', 'categorie_code', 'category', 'category_code'],
            'localisation' => ['localisation', 'localisation_code', 'location', 'location_code'],
            'etat' => ['etat', 'state', 'status'],
            'annee' => ['annee', 'annee_acquisition', 'year'],
            'marque' => ['marque', 'brand'],
            'modele' => ['modele', 'model'],
            'numero_serie' => ['numero_serie', 'serial', 'serial_number'],
            'valeur' => ['valeur', 'valeur_acquisition', 'value'],
            'observations' => ['observations', 'notes', 'commentaires'],
            'necessite_maintenance' => ['necessite_maintenance', 'maintenance_required'],
            'frequence' => ['frequence', 'frequence_maintenance_jours', 'maintenance_frequency_days'],
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

    private function extractImportRowData(array $row, array $headerIndex): array
    {
        $extract = fn (string $key): string => isset($headerIndex[$key]) ? trim((string) ($row[$headerIndex[$key]] ?? '')) : '';

        return [
            'site' => $extract('site'),
            'code_complet' => $extract('code_complet'),
            'nom' => $extract('nom'),
            'categorie' => $extract('categorie'),
            'localisation' => $extract('localisation'),
            'etat' => $extract('etat'),
            'annee' => $extract('annee'),
            'marque' => $extract('marque'),
            'modele' => $extract('modele'),
            'numero_serie' => $extract('numero_serie'),
            'valeur' => $extract('valeur'),
            'observations' => $extract('observations'),
            'necessite_maintenance' => $extract('necessite_maintenance'),
            'frequence' => $extract('frequence'),
        ];
    }

    private function resolveSite($sitesById, string $rawSite): ?Site
    {
        if ($rawSite === '') {
            return null;
        }

        if (ctype_digit($rawSite)) {
            $site = $sitesById->get((int) $rawSite);
            if ($site) {
                return $site;
            }
        }

        return $sitesById->first(fn (Site $site) => mb_strtoupper(trim($site->name)) === mb_strtoupper(trim($rawSite)));
    }

    private function resolveCodification(string $value, $byCode, $byLabel): ?CodificationElement
    {
        $needle = mb_strtoupper(trim($value));
        if ($needle === '') {
            return null;
        }

        return $byCode->get($needle) ?? $byLabel->get($needle);
    }

    private function normalizeEtat(string $etat): ?string
    {
        $value = mb_strtolower(trim($etat));
        $mapping = [
            'tres_bon' => 'tres_bon',
            'tres bon' => 'tres_bon',
            'excellent' => 'tres_bon',
            'bon' => 'bon',
            'mauvais' => 'mauvais',
            'defaillant' => 'mauvais',
        ];

        return $mapping[$value] ?? null;
    }

    private function normalizeBoolean(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'oui', 'yes', 'y'], true);
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function assertScopedReferences(array $validated, int $enterpriseId, $user): void
    {
        if (isset($validated['site_id'])) {
            $site = Site::query()
                ->where('id', (int) $validated['site_id'])
                ->where('enterprise_id', $enterpriseId)
                ->first();
            if (!$site) {
                throw new \InvalidArgumentException('Site non autorisé pour cette entreprise.');
            }

            if (!$user?->isEnterpriseAdmin() && (int) ($user?->site_id ?? 0) > 0 && (int) $site->id !== (int) $user->site_id) {
                throw new \InvalidArgumentException('Site non autorisé pour cet utilisateur.');
            }
        }

        if (isset($validated['categorie_id'])) {
            $category = CodificationElement::query()
                ->where('id', (int) $validated['categorie_id'])
                ->where('enterprise_id', $enterpriseId)
                ->where('type', 'categorie')
                ->first();
            if (!$category) {
                throw new \InvalidArgumentException('Catégorie non autorisée pour cette entreprise.');
            }
        }

        if (isset($validated['localisation_id'])) {
            $localisation = CodificationElement::query()
                ->where('id', (int) $validated['localisation_id'])
                ->where('enterprise_id', $enterpriseId)
                ->where('type', 'localisation')
                ->first();
            if (!$localisation) {
                throw new \InvalidArgumentException('Localisation non autorisée pour cette entreprise.');
            }
        }
    }

    private function buildPayloadWithPotentialRecodification(
        Equipement $equipement,
        array $payload,
        int $enterpriseId,
        string $enterpriseSigle,
        ?int $changedByUserId = null
    ): array {
        $structuralFields = ['site_id', 'categorie_id', 'localisation_id', 'nom_commun', 'annee_acquisition'];
        $shouldRecodify = false;
        foreach ($structuralFields as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            if ((string) $payload[$field] !== (string) $equipement->{$field}) {
                $shouldRecodify = true;
                break;
            }
        }

        if (!$shouldRecodify) {
            return $payload;
        }

        $categoryId = (int) ($payload['categorie_id'] ?? $equipement->categorie_id);
        $localisationId = (int) ($payload['localisation_id'] ?? $equipement->localisation_id);
        $nomCommun = trim((string) ($payload['nom_commun'] ?? $equipement->nom_commun));
        $annee = (int) ($payload['annee_acquisition'] ?? $equipement->annee_acquisition);

        $category = CodificationElement::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('type', 'categorie')
            ->find($categoryId);
        $localisation = CodificationElement::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('type', 'localisation')
            ->find($localisationId);

        if (!$category || !$localisation) {
            throw new \InvalidArgumentException('Catégorie/localisation invalide pour recodification.');
        }

        $nomCommunAbrege = strtoupper(substr($nomCommun, 0, 3));
        $indice = $equipement->indice;
        if (strtoupper((string) $equipement->nom_commun_abrege) !== $nomCommunAbrege) {
            $indice = Equipement::prochainIndice($nomCommunAbrege, $enterpriseId);
        }

        $newCode = Equipement::genererCodeComplet(
            $enterpriseSigle,
            (string) $category->code,
            $nomCommunAbrege,
            (string) $localisation->code,
            (string) $indice,
            $annee
        );

        if ($newCode !== (string) $equipement->code_complet && $this->isCodeAlreadyUsed($newCode, $enterpriseId, (int) $equipement->id)) {
            $indice = Equipement::prochainIndice($nomCommunAbrege, $enterpriseId);
            $newCode = Equipement::genererCodeComplet(
                $enterpriseSigle,
                (string) $category->code,
                $nomCommunAbrege,
                (string) $localisation->code,
                (string) $indice,
                $annee
            );
        }

        if ($newCode !== (string) $equipement->code_complet && $this->isCodeAlreadyUsed($newCode, $enterpriseId, (int) $equipement->id)) {
            throw new \InvalidArgumentException('Impossible de générer un code équipement unique.');
        }

        if ($newCode !== (string) $equipement->code_complet) {
            $this->closePrimaryCodeHistory(
                $equipement,
                $enterpriseId,
                $changedByUserId,
                'recodification'
            );
            $this->recordPrimaryCodeHistory(
                $equipement,
                $enterpriseId,
                $newCode,
                $changedByUserId,
                'recodification',
                now(),
                null,
                null
            );
        }

        $payload['nom_commun_abrege'] = $nomCommunAbrege;
        $payload['indice'] = (string) $indice;
        $payload['code_complet'] = $newCode;

        return $payload;
    }

    private function isCodeAlreadyUsed(string $codeComplet, int $enterpriseId, int $exceptEquipementId): bool
    {
        return Equipement::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('code_complet', $codeComplet)
            ->where('id', '!=', $exceptEquipementId)
            ->exists();
    }

    private function applyCustomCodeUpdate(
        Equipement $equipement,
        array $payload,
        int $enterpriseId,
        ?int $changedByUserId
    ): array {
        if (array_key_exists('code_complet', $payload)) {
            $code = trim((string) $payload['code_complet']);
            if ($code === '') {
                throw new \InvalidArgumentException('Le code équipement est obligatoire en mode personnalisé.');
            }

            $exists = Equipement::query()
                ->where('enterprise_id', $enterpriseId)
                ->where('code_complet', $code)
                ->where('id', '!=', (int) $equipement->id)
                ->exists();
            if ($exists) {
                throw new \InvalidArgumentException('Ce code équipement est déjà utilisé dans l’entreprise.');
            }

            if ($code !== (string) $equipement->code_complet) {
                $this->closePrimaryCodeHistory(
                    $equipement,
                    $enterpriseId,
                    $changedByUserId,
                    'custom_code_change'
                );
                $this->recordPrimaryCodeHistory(
                    $equipement,
                    $enterpriseId,
                    $code,
                    $changedByUserId,
                    'custom_code_change',
                    now(),
                    null,
                    'Modification manuelle du code'
                );
            }

            $payload['code_complet'] = $code;
        }

        if (array_key_exists('nom_commun', $payload) && !array_key_exists('nom_commun_abrege', $payload)) {
            $payload['nom_commun_abrege'] = strtoupper(substr((string) $payload['nom_commun'], 0, 3));
        }

        return $payload;
    }

    private function recordInitialCodeHistory(
        Equipement $equipement,
        int $enterpriseId,
        ?int $changedByUserId,
        string $reason = 'initial',
        ?string $notes = 'Création équipement'
    ): void
    {
        $code = (string) $equipement->code_complet;
        if ($code === '') {
            return;
        }

        EquipementCodeAlias::query()->firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'code_alias' => $code,
            ],
            [
                'equipement_id' => $equipement->id,
                'change_reason' => $reason,
                'changed_by' => $changedByUserId,
                'effective_from' => $equipement->created_at ?? now(),
                'effective_to' => null,
                'is_primary_at_time' => true,
                'notes' => $notes,
                'metadata' => [
                    'to' => $code,
                ],
            ]
        );
    }

    private function closePrimaryCodeHistory(
        Equipement $equipement,
        int $enterpriseId,
        ?int $changedByUserId,
        string $reason
    ): void {
        $code = (string) $equipement->code_complet;
        if ($code === '') {
            return;
        }

        $existing = EquipementCodeAlias::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('equipement_id', $equipement->id)
            ->where('code_alias', $code)
            ->latest('effective_from')
            ->first();

        if ($existing) {
            $existing->update([
                'change_reason' => $reason,
                'changed_by' => $changedByUserId,
                'effective_from' => $existing->effective_from ?? ($equipement->created_at ?? now()),
                'effective_to' => now(),
                'is_primary_at_time' => true,
            ]);
            return;
        }

        EquipementCodeAlias::query()->create([
            'enterprise_id' => $enterpriseId,
            'equipement_id' => $equipement->id,
            'code_alias' => $code,
            'change_reason' => $reason,
            'changed_by' => $changedByUserId,
            'effective_from' => $equipement->created_at ?? now(),
            'effective_to' => now(),
            'is_primary_at_time' => true,
            'notes' => 'Historique avant recodification',
            'metadata' => [
                'from' => $code,
            ],
        ]);
    }

    private function recordPrimaryCodeHistory(
        Equipement $equipement,
        int $enterpriseId,
        string $code,
        ?int $changedByUserId,
        string $reason,
        $effectiveFrom,
        $effectiveTo,
        ?string $notes
    ): void {
        if ($code === '') {
            return;
        }

        EquipementCodeAlias::query()->firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'code_alias' => $code,
            ],
            [
                'equipement_id' => $equipement->id,
                'change_reason' => $reason,
                'changed_by' => $changedByUserId,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_primary_at_time' => true,
                'notes' => $notes,
                'metadata' => [
                    'to' => $code,
                ],
            ]
        );
    }
}
