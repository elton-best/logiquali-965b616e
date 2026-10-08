<?php

namespace App\Modules\Processes\Controllers;

use App\Models\Document;
use App\Models\Plan;
use App\Models\Site;
use App\Services\DocumentSyncService;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesGeneratedDocumentContext;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\ProcessResource;
use App\Models\Action;
use App\Models\Process;
use App\Models\ProcessIndicator;
use App\Models\ProcessObjective;
use App\Models\ProcessRiskOpportunity;
use App\Models\TeamMember;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentBrandingService;
use App\Services\DocumentTypeResolver;
use App\Services\ProcessService;
use App\Services\WorkingDaysService;
use App\Services\ObjectiveCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProcessController extends Controller
{
    use ResolvesGeneratedDocumentContext;

    protected $processService;
    protected WorkingDaysService $workingDaysService;

    public function __construct(ProcessService $processService, WorkingDaysService $workingDaysService)
    {
        $this->processService = $processService;
        $this->workingDaysService = $workingDaysService;
        
        // Apply policy authorization except for store from application scope
        $this->authorizeResource(Process::class, 'process', [
            'except' => ['store']
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Process::with([
            'site', 'pilot', 'copilot', 'copilots', 'parentProcess',
            'indicators', 'risksOpportunities', 'processObjectives.indicator'
        ]);

        // Scope access by user type / enterprise / site context
        if ($user->user_type !== 'super_admin') {
            if ($request->filled('site_id')) {
                $siteId = (int) $request->site_id;
                if ($user->enterprise_id) {
                    $query->whereHas('site', function ($siteQuery) use ($user) {
                        $siteQuery->where('enterprise_id', $user->enterprise_id);
                    });
                }
                $query->where('site_id', $siteId);
            } elseif ($user->site_id) {
                $query->where('site_id', $user->site_id);
            } elseif ($user->enterprise_id) {
                $query->whereHas('site', function ($siteQuery) use ($user) {
                    $siteQuery->where('enterprise_id', $user->enterprise_id);
                });
            }
        } elseif ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $processes = $query->paginate($request->get('per_page', 20));

        return ProcessResource::collection($processes);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'site_id' => 'required|exists:sites,id',
                'name' => 'sometimes|string|max:255',
                'title' => 'sometimes|string|max:255',
                'abbreviation' => 'nullable|string|max:10',
                'type' => 'sometimes|in:management,operational,support,pilotage,operationnel,realization,realisation',
                'category' => 'sometimes|in:pilotage,support,operationnel,mesure_amelioration,management,realization,realisation',
                'status' => 'sometimes|in:draft,active,inactive',
                'pilot_id' => 'nullable|exists:users,id',
                'copilot_id' => 'nullable|exists:users,id',
                'copilot_ids' => 'nullable|array',
                'copilot_ids.*' => 'integer|exists:users,id',
                'purpose' => 'nullable|string',
                'finalite' => 'nullable|string',
                'observation' => 'nullable|string',
                'acteurs' => 'nullable|array',
                'ressources' => 'nullable|array',
                'methodes' => 'nullable|array',
                'interfaces' => 'nullable|array',
                'aspect_qualite' => 'nullable|boolean',
                'aspect_environnement' => 'nullable|boolean',
                'aspect_sante_securite' => 'nullable|boolean',
                'normes_iso' => 'nullable|array',
                'sequences' => 'nullable|array',
                'sequences.*.input_description' => 'nullable|string',
                'sequences.*.activity_description' => 'nullable|string',
                'sequences.*.output_description' => 'nullable|string',
                'sequences.*.sub_activities' => 'nullable|array',
                'sequences.*.sub_activities.*' => 'nullable|string|max:255',
                'sequences.*.supplier_processes' => 'nullable|array',
                'sequences.*.client_processes' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[ProcessController] Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        if (!isset($validated['title']) && isset($validated['name'])) {
            $validated['title'] = $validated['name'];
        }

        $validated = $this->normalizeProcessTypeAndCategory($validated);

        if (!isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        if (!isset($validated['purpose']) && !isset($validated['finalite'])) {
            $validated['purpose'] = 'Processus créé depuis le domaine d\'application';
        } elseif (isset($validated['finalite']) && !isset($validated['purpose'])) {
            $validated['purpose'] = $validated['finalite'];
        }

        if (!isset($validated['pilot_id'])) {
            $validated['pilot_id'] = Auth::id() ?? 1;
        }

        $sequences = $validated['sequences'] ?? [];
        $copilotIds = collect($validated['copilot_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->unique()->values()->all();
        unset($validated['sequences']);
        unset($validated['copilot_ids']);

        $this->ensureSubActivitiesColumnIfProvided($sequences);

        DB::beginTransaction();
        try {
            $process = $this->processService->createProcess($validated, Auth::id() ?? 1);

            if (!empty($sequences)) {
                foreach ($sequences as $index => $seqData) {
                    $payload = $this->buildSequencePayload($seqData, $index);
                    $process->sequences()->create($payload);
                }
            }

            $this->syncProcessCopilots($process, $copilotIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ProcessController] Store failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return new ProcessResource($process->load(['sequences', 'copilots']));
    }

    public function show(Process $process)
    {
        $process->load([
            'site', 'pilot', 'copilot', 'copilots', 'validator',
            'parentProcess', 'childProcesses',
            'indicators.values', 'indicators.responsibleUser',
            'risksOpportunities.responsibleUser',
            'isoCoverages',
            'reviews.leader',
            'sequences.responsibleUser',
            'versions.author',
            'processObjectives.indicator',
            'risks',
            'opportunities',
        ]);

        return new ProcessResource($process);
    }

    public function update(Request $request, Process $process)
    {
        Log::info('[ProcessController] UPDATE - Raw request data:', $request->all());
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'abbreviation' => 'nullable|string|max:10',
            'type' => 'sometimes|in:management,operational,support,pilotage,operationnel,realization,realisation',
            'category' => 'sometimes|in:pilotage,support,operationnel,mesure_amelioration,management,realization,realisation',
            'status' => 'sometimes|in:draft,active,inactive,in_review,validated,obsolete',
            'pilot_id' => 'sometimes|exists:users,id',
            'copilot_id' => 'nullable|exists:users,id',
            'copilot_ids' => 'nullable|array',
            'copilot_ids.*' => 'integer|exists:users,id',
            'purpose' => 'sometimes|string',
            'finalite' => 'nullable|string',
            'observation' => 'nullable|string',
            'acteurs' => 'nullable|array',
            'ressources' => 'nullable|array',
            'methodes' => 'nullable|array',
            'interfaces' => 'nullable|array',
            'aspect_qualite' => 'nullable|boolean',
            'aspect_environnement' => 'nullable|boolean',
            'aspect_sante_securite' => 'nullable|boolean',
            'normes_iso' => 'nullable|array',
            'sequences' => 'nullable|array',
            'sequences.*.input_description' => 'nullable|string',
            'sequences.*.activity_description' => 'nullable|string',
            'sequences.*.output_description' => 'nullable|string',
            'sequences.*.sub_activities' => 'nullable|array',
            'sequences.*.sub_activities.*' => 'nullable|string|max:255',
            'sequences.*.supplier_processes' => 'nullable|array',
            'sequences.*.client_processes' => 'nullable|array',
        ]);
        
        Log::info('[ProcessController] UPDATE - Validated sequences:', $validated['sequences'] ?? []);

        if (!isset($validated['title']) && isset($validated['name'])) {
            $validated['title'] = $validated['name'];
        }

        $validated = $this->normalizeProcessTypeAndCategory($validated);

        if (!isset($validated['purpose']) && isset($validated['finalite'])) {
            $validated['purpose'] = $validated['finalite'];
        }

        $sequences = $validated['sequences'] ?? null;
        $copilotIds = collect($validated['copilot_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->unique()->values()->all();
        unset($validated['sequences']);
        unset($validated['copilot_ids']);

        if (is_array($sequences)) {
            $this->ensureSubActivitiesColumnIfProvided($sequences);
        }

        DB::beginTransaction();
        try {
            $process = $this->processService->updateProcess(
                $process,
                $validated,
                Auth::id() ?? 1,
                $request->boolean('create_new_version', false)
            );

            if (is_array($sequences)) {
                // Supprimer toutes les séquences existantes
                $process->sequences()->delete();

                // Créer les nouvelles séquences
                foreach ($sequences as $index => $seqData) {
                    $payload = $this->buildSequencePayload($seqData, $index);
                    Log::info('[ProcessController] Creating sequence:', $payload);
                    $process->sequences()->create($payload);
                }
            }

            $this->syncProcessCopilots($process, $copilotIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ProcessController] Update failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return new ProcessResource($process->fresh()->load(['sequences', 'copilots']));
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return response()->json(['message' => 'Processus supprimé'], 204);
    }

    public function addIndicator(Request $request, Process $process)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:efficacite,efficience,conformite,performance',
            'category' => 'required|in:qualite,environnement,sante_securite,global',
            'unit' => 'required|string|max:50',
            'target_value' => 'nullable|numeric',
        ]);

        $indicator = $this->processService->createIndicator($process, $validated, $request->user()->id);

        return response()->json(['data' => $indicator], 201);
    }

    public function addRiskOpportunity(Request $request, Process $process)
    {
        $validated = $request->validate([
            'type' => 'required|in:risque,opportunite',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'normes_iso' => 'nullable|array',
            'cause' => 'nullable|string',
            'consequence' => 'nullable|string',
            'probabilite' => 'required|integer|min:1|max:4',
            'gravite' => 'required|integer|min:1|max:4',
            'strategie' => 'nullable|in:accepter,reduire,transferer,eviter,exploiter',
            'actions_prevues' => 'nullable|string',
            'planned_actions' => 'nullable',
            'responsible_user_id' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
            'status' => 'nullable|in:identifie,en_cours,traite,surveille,cloture,a_traiter',
            'probabilite_residuelle' => 'nullable|integer|min:1|max:4',
            'gravite_residuelle' => 'nullable|integer|min:1|max:4',
        ]);

        $validated = $this->normalizeRiskOpportunityActionsPayload($validated);

        if (empty(trim((string) ($validated['description'] ?? ''))) && !empty($validated['title'])) {
            $validated['description'] = $validated['title'];
        }

        if (array_key_exists('status', $validated) && $validated['status']) {
            $validated['status'] = $this->normalizeRiskOpportunityStatus($validated['status']);
        }

        $riskOpp = $this->processService->createRiskOpportunity($process, $validated, $request->user()->id);

        return response()->json(['data' => $riskOpp], 201);
    }

    public function addIsoCoverage(Request $request, Process $process)
    {
        $validated = $request->validate([
            'norme' => 'required|string|max:30',
            'version' => 'nullable|string|max:20',
            'clause_number' => 'required|string|max:20',
            'clause_title' => 'required|string|max:255',
            'clause_description' => 'nullable|string',
            'coverage_level' => 'required|in:none,partial,full',
            'coverage_percentage' => 'nullable|numeric|min:0|max:100',
            'coverage_comment' => 'nullable|string',
            'evidence' => 'nullable|array',
            'conformity_status' => 'nullable|in:conforme,partiellement_conforme,non_conforme,na',
            'last_audit_date' => 'nullable|date',
            'next_audit_date' => 'nullable|date|after_or_equal:last_audit_date',
        ]);

        $coverage = $this->processService->addIsoCoverage($process, $validated);

        return response()->json(['data' => $coverage], 201);
    }

    public function addIndicatorValue(Request $request, ProcessIndicator $indicator)
    {
        $validated = $request->validate([
            'measurement_date' => 'required|date',
            'period' => 'nullable|string|max:100',
            'value' => 'required|numeric',
            'comment' => 'nullable|string|max:1000',
        ]);

        $this->processService->addIndicatorValue($indicator, $validated, $request->user()->id);

        return response()->json([
            'data' => $indicator->fresh()->load([
                'values' => fn ($query) => $query->latest('measurement_date')->limit(20),
            ]),
        ], 201);
    }

    public function listReviewDashboardActions(Request $request, Process $process)
    {
        $this->authorize('viewReviewDashboardActions', $process);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $canViewAll = $this->canViewAllReviewActions($process, $user);

        $query = Action::query()
            ->with(['responsible', 'planAction', 'workflowState', 'axes'])
            ->where('process_id', $process->id);

        if (!$canViewAll) {
            $query->where('responsible_id', (int) $user->id);
        }

        $actions = $query->latest()->paginate($request->integer('per_page', 50));

        return response()->json($actions);
    }

    private function syncProcessCopilots(Process $process, array $copilotIds): void
    {
        TeamMember::query()
            ->where('process_id', $process->id)
            ->where('role', 'copilot')
            ->delete();

        foreach ($copilotIds as $userId) {
            TeamMember::create([
                'site_id' => (int) $process->site_id,
                'process_id' => (int) $process->id,
                'user_id' => (int) $userId,
                'role' => 'copilot',
                'is_active' => true,
            ]);
        }
    }

    public function listRisksOpportunities(Request $request)
    {
        $query = ProcessRiskOpportunity::with(['process', 'responsibleUser']);

        if ($request->filled('site_id')) {
            $siteId = $request->integer('site_id');
            $query->whereHas('process', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->integer('process_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $items]);
    }

    public function showRiskOpportunity(ProcessRiskOpportunity $riskOpportunity)
    {
        return response()->json([
            'data' => $riskOpportunity->load(['process', 'responsibleUser']),
        ]);
    }

    public function updateRiskOpportunity(Request $request, ProcessRiskOpportunity $riskOpportunity)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:risque,opportunite',
            'normes_iso' => 'nullable|array',
            'cause' => 'nullable|string',
            'consequence' => 'nullable|string',
            'probabilite' => 'sometimes|integer|min:1|max:4',
            'gravite' => 'sometimes|integer|min:1|max:4',
            'strategie' => 'nullable|in:accepter,reduire,transferer,eviter,exploiter',
            'actions_prevues' => 'nullable|string',
            'planned_actions' => 'nullable',
            'responsible_user_id' => 'nullable|exists:users,id',
            'target_date' => 'nullable|date',
            'status' => 'nullable|in:identifie,en_cours,traite,surveille,cloture,a_traiter',
            'probabilite_residuelle' => 'nullable|integer|min:1|max:4',
            'gravite_residuelle' => 'nullable|integer|min:1|max:4',
        ]);

        $validated = $this->normalizeRiskOpportunityActionsPayload($validated);

        if (array_key_exists('description', $validated) && trim((string) ($validated['description'] ?? '')) === '') {
            $validated['description'] = null;
        }

        if (array_key_exists('status', $validated) && $validated['status']) {
            $validated['status'] = $this->normalizeRiskOpportunityStatus($validated['status']);
        }

        $riskOpportunity->update($validated);

        return response()->json([
            'data' => $riskOpportunity->fresh()->load(['process', 'responsibleUser']),
        ]);
    }

    public function deleteRiskOpportunity(ProcessRiskOpportunity $riskOpportunity)
    {
        $riskOpportunity->delete();

        return response()->json(['message' => 'Risque/opportunite de processus supprime'], 200);
    }

    public function updateRiskEvaluation(Request $request, ProcessRiskOpportunity $riskOpportunity)
    {
        $validated = $request->validate([
            'probabilite' => 'required|integer|min:1|max:4',
            'gravite' => 'required|integer|min:1|max:4',
            'probabilite_residuelle' => 'nullable|integer|min:1|max:4',
            'gravite_residuelle' => 'nullable|integer|min:1|max:4',
        ]);

        $riskOpportunity->update($validated);

        return response()->json([
            'data' => $riskOpportunity->fresh()->load(['process', 'responsibleUser']),
        ]);
    }

    public function exportRisksOpportunitiesXlsx(Request $request)
    {
        $user = $request->user();
        $siteId = (int) $request->integer('site_id');

        $query = ProcessRiskOpportunity::query()->with(['process.site']);

        if ($siteId > 0) {
            $query->whereHas('process', fn ($processQuery) => $processQuery->where('site_id', $siteId));
        } elseif ($user?->site_id) {
            $query->whereHas('process', fn ($processQuery) => $processQuery->where('site_id', (int) $user->site_id));
        } elseif ($user?->enterprise_id) {
            $query->whereHas('process.site', fn ($siteQuery) => $siteQuery->where('enterprise_id', (int) $user->enterprise_id));
        }

        $items = $query->orderBy('process_id')->orderBy('id')->get();

        $allUserIds = collect($items)
            ->flatMap(function (ProcessRiskOpportunity $item) {
                $plannedActions = is_array($item->planned_actions) ? $item->planned_actions : [];

                return collect($plannedActions)->flatMap(function ($action) {
                    if (!is_array($action)) {
                        return [];
                    }

                    return array_filter([
                        $action['responsible_user_id'] ?? null,
                        ...((is_array($action['implicated_user_ids'] ?? null) ? $action['implicated_user_ids'] : [])),
                    ]);
                });
            })
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $userNames = User::query()
            ->whereIn('id', $allUserIds)
            ->get()
            ->mapWithKeys(fn (User $entry) => [(int) $entry->id => (string) ($entry->name ?: $entry->email ?: ('Utilisateur #' . $entry->id))])
            ->all();

        $groupedRows = $this->buildRisksOpportunityExportRows($items, $userNames);

        $templatePath = base_path('../frontend/public/plan-maitrise-risques-opportunites-template.xlsx');
        $spreadsheet = is_file($templatePath)
            ? IOFactory::load($templatePath)
            : new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan de maîtrise');

        $this->prepareRisksOpportunityExportSheet($sheet);
        $this->fillRisksOpportunityExportSheet($sheet, $groupedRows);

        $filePath = tempnam(sys_get_temp_dir(), 'risk_plan_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);

        return response()->download($filePath, 'plan_risques_opportunites_' . now()->format('Y-m-d') . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    private function normalizeProcessTypeAndCategory(array $data): array
    {
        $typeAliasToDb = [
            'management' => 'management',
            'pilotage' => 'management',
            'support' => 'support',
            'operational' => 'operational',
            'operationnel' => 'operational',
            'realization' => 'operational',
            'realisation' => 'operational',
        ];

        $categoryAliasToDb = [
            'pilotage' => 'pilotage',
            'support' => 'support',
            'operationnel' => 'operationnel',
            'realization' => 'operationnel',
            'realisation' => 'operationnel',
            'management' => 'pilotage',
            'operational' => 'operationnel',
        ];

        if (isset($data['type']) && is_string($data['type'])) {
            $normalizedType = strtolower(trim($data['type']));
            if (isset($typeAliasToDb[$normalizedType])) {
                $data['type'] = $typeAliasToDb[$normalizedType];
            } else {
                unset($data['type']);
            }
        }

        if (isset($data['category']) && is_string($data['category'])) {
            $normalizedCategory = strtolower(trim($data['category']));
            if (isset($categoryAliasToDb[$normalizedCategory])) {
                $data['category'] = $categoryAliasToDb[$normalizedCategory];
            } else {
                unset($data['category']);
            }
        }

        if (!isset($data['category']) && isset($data['type'])) {
            $data['category'] = match ($data['type']) {
                'management' => 'pilotage',
                'support' => 'support',
                default => 'operationnel',
            };
        }

        if (!isset($data['type']) && isset($data['category'])) {
            $data['type'] = match ($data['category']) {
                'pilotage' => 'management',
                'support' => 'support',
                default => 'operational',
            };
        }

        return $data;
    }

    private function canViewAllReviewActions(Process $process, mixed $user): bool
    {
        if (!$user) {
            return false;
        }

        if (($user->user_type ?? null) === 'super_admin') {
            return true;
        }

        $userId = (int) ($user->id ?? 0);
        if ($userId > 0) {
            if ((int) ($process->pilot_id ?? 0) === $userId) {
                return true;
            }
            if ((int) ($process->copilot_id ?? 0) === $userId) {
                return true;
            }
        }

        return (bool) (
            $user->can('processes.manage')
            || $user->can('processes.update')
        );
    }

    private function normalizeRiskOpportunityStatus(string $status): string
    {
        $mapping = [
            'a_traiter' => 'identifie',
            'identifie' => 'identifie',
            'en_cours' => 'en_cours',
            'traite' => 'traite',
            'surveille' => 'surveille',
            'cloture' => 'cloture',
        ];

        return $mapping[$status] ?? 'identifie';
    }

    private function normalizeSingleRiskAction(mixed $item): ?array
    {
        if (is_string($item)) {
            $title = trim($item);
            if ($title === '' || str_starts_with($title, '[META]')) {
                return null;
            }

            return [
                'title' => $title,
                'description' => $title,
            ];
        }

        if (!is_array($item)) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? $item['description'] ?? $item['action'] ?? ''));
        if ($title === '' || str_starts_with($title, '[META]')) {
            return null;
        }

        $normalized = [
            'title' => $title,
            'description' => trim((string) ($item['description'] ?? $title)),
        ];

        if (!empty($item['action_type']) && in_array($item['action_type'], ['preventive', 'corrective', 'control'], true)) {
            $normalized['action_type'] = $item['action_type'];
        }

        if (array_key_exists('responsible_user_id', $item)) {
            $normalized['responsible_user_id'] = $item['responsible_user_id'];
        }

        if (!empty($item['responsible'])) {
            $normalized['responsible'] = (string) $item['responsible'];
        }

        if (!empty($item['due_date'])) {
            $normalized['due_date'] = (string) $item['due_date'];
        }

        if (!empty($item['deadline_frequency'])) {
            $normalized['deadline_frequency'] = (string) $item['deadline_frequency'];
        }

        if (!empty($item['status'])) {
            $normalized['status'] = (string) $item['status'];
        }

        if (!empty($item['implicated_user_ids']) && is_array($item['implicated_user_ids'])) {
            $normalized['implicated_user_ids'] = array_values($item['implicated_user_ids']);
        }

        return $normalized;
    }

    private function formatRiskActionForDisplay(mixed $item): string
    {
        if (is_string($item)) {
            return trim($item);
        }

        if (!is_array($item)) {
            return '';
        }

        $title = trim((string) ($item['title'] ?? $item['description'] ?? $item['action'] ?? ''));
        if ($title === '') {
            return '';
        }

        $typeLabel = match ($item['action_type'] ?? null) {
            'preventive' => 'Préventive',
            'corrective' => 'Corrective',
            'control' => 'Maîtrise',
            default => '',
        };

        return $typeLabel !== '' ? "[{$typeLabel}] {$title}" : $title;
    }

    private function extractRiskActionTypes(array $plannedActions): array
    {
        $types = array_values(array_unique(array_filter(array_map(
            static function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $type = $item['action_type'] ?? null;
                return in_array($type, ['preventive', 'corrective', 'control'], true) ? $type : null;
            },
            $plannedActions
        ))));

        return $types;
    }

    private function normalizeRiskOpportunityActionsPayload(array $payload): array
    {
        if (array_key_exists('planned_actions', $payload)) {
            $planned = $payload['planned_actions'];

            if (is_string($planned)) {
                try {
                    $decoded = json_decode($planned, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $payload['planned_actions'] = array_values(array_filter(array_map(
                            fn ($item) => $this->normalizeSingleRiskAction($item),
                            $decoded
                        )));
                    } else {
                        $payload['planned_actions'] = [];
                    }
                } catch (\Throwable) {
                    $lines = preg_split('/\r\n|\r|\n/', $planned) ?: [];
                    $payload['planned_actions'] = array_values(array_filter(array_map(
                        fn ($line) => $this->normalizeSingleRiskAction($line),
                        $lines
                    )));
                }
            } elseif (is_array($planned)) {
                $payload['planned_actions'] = array_values(array_filter(array_map(
                    fn ($item) => $this->normalizeSingleRiskAction($item),
                    $planned
                )));
            } elseif (!is_array($planned) && $planned !== null) {
                throw ValidationException::withMessages([
                    'planned_actions' => 'Le champ planned_actions doit être un tableau, une chaîne ou null.',
                ]);
            }
        }

        if (array_key_exists('actions_prevues', $payload) && !array_key_exists('planned_actions', $payload)) {
            $actions = trim((string) ($payload['actions_prevues'] ?? ''));
            if ($actions !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $actions) ?: [];
                $payload['planned_actions'] = array_values(array_filter(array_map(
                    fn ($line) => $this->normalizeSingleRiskAction($line),
                    $lines
                )));
            }
        }

        if (
            array_key_exists('planned_actions', $payload)
            && !array_key_exists('actions_prevues', $payload)
            && is_array($payload['planned_actions'])
        ) {
            $payload['actions_prevues'] = implode("\n", array_values(array_filter(array_map(
                fn ($item) => $this->formatRiskActionForDisplay($item),
                $payload['planned_actions']
            ))));
        }

        if (array_key_exists('planned_actions', $payload) && is_array($payload['planned_actions'])) {
            $payload['action_types'] = $this->extractRiskActionTypes($payload['planned_actions']);
        }

        return $payload;
    }

    private function buildRisksOpportunityExportRows($items, array $userNames): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $processId = (int) ($item->process_id ?? 0);
            $processName = (string) ($item->process?->title ?: $item->process?->name ?: ('Processus #' . $processId));

            if (!isset($grouped[$processId])) {
                $grouped[$processId] = [
                    'processName' => $processName,
                    'risks' => [],
                    'opportunities' => [],
                ];
            }

            $plannedActions = is_array($item->planned_actions) ? $item->planned_actions : [];
            $actionsToExport = !empty($plannedActions) ? $plannedActions : [[]];

            foreach ($actionsToExport as $actionIndex => $action) {
                $action = is_array($action) ? $action : [];
                $responsibleId = (int) ($action['responsible_user_id'] ?? 0);
                $responsibleName = $responsibleId > 0 ? ($userNames[$responsibleId] ?? '') : '';

                $implicatedNames = collect(is_array($action['implicated_user_ids'] ?? null) ? $action['implicated_user_ids'] : [])
                    ->map(fn ($id) => $userNames[(int) $id] ?? null)
                    ->filter()
                    ->implode(', ');

                $row = [
                    'id' => (int) $item->id,
                    'description' => (string) ($item->description ?: $item->title ?: ''),
                    'cause' => (string) ($item->cause ?: ''),
                    'probabilite' => (int) ($item->probabilite ?: 1),
                    'gravite' => (int) ($item->gravite ?: 1),
                    'score' => (int) ($item->criticite ?: (($item->probabilite ?: 1) * ($item->gravite ?: 1))),
                    'action_type_label' => $item->type === 'risque' ? $this->riskActionTypeLabel($action['action_type'] ?? null) : '',
                    'actions_prevues' => (string) ($action['description'] ?? $action['title'] ?? $action['action'] ?? ''),
                    'responsable_name' => $responsibleName,
                    'responsables_implique_display' => $implicatedNames,
                    'delai_frequence' => (string) ($action['deadline_frequency'] ?? $action['due_date'] ?? ''),
                    'action_index' => $actionIndex,
                ];

                if ($item->type === 'risque') {
                    $grouped[$processId]['risks'][] = $row;
                } else {
                    $grouped[$processId]['opportunities'][] = $row;
                }
            }
        }

        uasort($grouped, fn ($a, $b) => strcmp($a['processName'], $b['processName']));

        return array_values($grouped);
    }

    private function prepareRisksOpportunityExportSheet($sheet): void
    {
        $sheet->mergeCells('D2:H3');
        $sheet->mergeCells('B5:B6');
        $sheet->mergeCells('C5:C6');
        $sheet->mergeCells('D5:D6');
        $sheet->mergeCells('E5:E6');
        $sheet->mergeCells('F5:H5');
        $sheet->mergeCells('I5:I6');
        $sheet->mergeCells('J5:J6');
        $sheet->mergeCells('K5:K6');
        $sheet->mergeCells('L5:L6');
        $sheet->mergeCells('M5:M6');
        $sheet->mergeCells('N5:O5');
        $sheet->mergeCells('P5:P6');
        $sheet->mergeCells('Q5:S5');
        $sheet->mergeCells('T5:T6');
        $sheet->mergeCells('U5:U6');
        $sheet->mergeCells('V5:V6');
        $sheet->mergeCells('W5:W6');
        $sheet->mergeCells('X5:Y5');

        $sheet->setCellValue('B5', 'N°');
        $sheet->setCellValue('C5', 'PROCESSUS');
        $sheet->setCellValue('D5', 'RISQUES');
        $sheet->setCellValue('E5', 'CAUSES PROFONDES');
        $sheet->setCellValue('F5', 'EVALUATION');
        $sheet->setCellValue('I5', "TYPE D'ACTION");
        $sheet->setCellValue('J5', 'ACTIONS');
        $sheet->setCellValue('K5', 'RESPONSABLE');
        $sheet->setCellValue('L5', 'RESPONSABLE(S) IMPLIQUES');
        $sheet->setCellValue('M5', 'DELAI DE MISE EN OEUVRE / FREQUENCE');
        $sheet->setCellValue('N5', 'SUIVI');
        $sheet->setCellValue('P5', 'OPPORTUNITES');
        $sheet->setCellValue('Q5', 'EVALUATION');
        $sheet->setCellValue('T5', 'ACTIONS');
        $sheet->setCellValue('U5', 'RESPONSABLE');
        $sheet->setCellValue('V5', 'RESPONSABLE(S) IMPLIQUES');
        $sheet->setCellValue('W5', 'DELAI DE MISE EN OEUVRE / FREQUENCE');
        $sheet->setCellValue('X5', 'SUIVI');

        $sheet->setCellValue('F6', 'Probabilité');
        $sheet->setCellValue('G6', 'Gravité');
        $sheet->setCellValue('H6', 'Criticité');
        $sheet->setCellValue('N6', 'Efficacité des actions');
        $sheet->setCellValue('O6', 'Commentaires');
        $sheet->setCellValue('Q6', 'Probabilité');
        $sheet->setCellValue('R6', 'Pertinence');
        $sheet->setCellValue('S6', 'Niveau de priorité');
        $sheet->setCellValue('X6', 'Efficacité des actions');
        $sheet->setCellValue('Y6', 'Commentaires');

        $this->applyScoreFill($sheet, 'H6', 12, 'risque');
        $this->applyScoreFill($sheet, 'S6', 12, 'opportunite');
    }

    private function fillRisksOpportunityExportSheet($sheet, array $groupedRows): void
    {
        $rowIndex = 7;
        $sequence = 1;

        foreach ($groupedRows as $group) {
            $processStartRow = $rowIndex;
            $rowCount = max(count($group['risks']), count($group['opportunities']), 1);

            for ($i = 0; $i < $rowCount; $i++) {
                $risk = $group['risks'][$i] ?? null;
                $opportunity = $group['opportunities'][$i] ?? null;

                $sheet->setCellValue("B{$rowIndex}", $sequence);
                $sheet->setCellValue("C{$rowIndex}", $i === 0 ? $group['processName'] : '');
                $sheet->setCellValue("D{$rowIndex}", $risk['description'] ?? '');
                $sheet->setCellValue("E{$rowIndex}", $risk['cause'] ?? '');
                $sheet->setCellValue("F{$rowIndex}", $risk['probabilite'] ?? '');
                $sheet->setCellValue("G{$rowIndex}", $risk['gravite'] ?? '');
                $sheet->setCellValue("H{$rowIndex}", $risk['score'] ?? '');
                $sheet->setCellValue("I{$rowIndex}", $risk['action_type_label'] ?? '');
                $sheet->setCellValue("J{$rowIndex}", $risk['actions_prevues'] ?? '');
                $sheet->setCellValue("K{$rowIndex}", $risk['responsable_name'] ?? '');
                $sheet->setCellValue("L{$rowIndex}", $risk['responsables_implique_display'] ?? '');
                $sheet->setCellValue("M{$rowIndex}", $risk['delai_frequence'] ?? '');
                $sheet->setCellValue("N{$rowIndex}", '');
                $sheet->setCellValue("O{$rowIndex}", '');

                $sheet->setCellValue("P{$rowIndex}", $opportunity['description'] ?? '');
                $sheet->setCellValue("Q{$rowIndex}", $opportunity['probabilite'] ?? '');
                $sheet->setCellValue("R{$rowIndex}", $opportunity['gravite'] ?? '');
                $sheet->setCellValue("S{$rowIndex}", $opportunity['score'] ?? '');
                $sheet->setCellValue("T{$rowIndex}", $opportunity['actions_prevues'] ?? '');
                $sheet->setCellValue("U{$rowIndex}", $opportunity['responsable_name'] ?? '');
                $sheet->setCellValue("V{$rowIndex}", $opportunity['responsables_implique_display'] ?? '');
                $sheet->setCellValue("W{$rowIndex}", $opportunity['delai_frequence'] ?? '');
                $sheet->setCellValue("X{$rowIndex}", '');
                $sheet->setCellValue("Y{$rowIndex}", '');

                if (isset($risk['score']) && $risk['score'] !== '') {
                    $this->applyScoreFill($sheet, "H{$rowIndex}", (int) $risk['score'], 'risque');
                }

                if (isset($opportunity['score']) && $opportunity['score'] !== '') {
                    $this->applyScoreFill($sheet, "S{$rowIndex}", (int) $opportunity['score'], 'opportunite');
                }

                $rowIndex++;
                $sequence++;
            }

            if ($rowIndex - 1 > $processStartRow) {
                $sheet->mergeCells("C{$processStartRow}:C" . ($rowIndex - 1));
            }
        }

        foreach (range('B', 'Y') as $column) {
            $sheet->getStyle("{$column}5:{$column}{$rowIndex}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
        }
    }

    private function applyScoreFill($sheet, string $cell, int $score, string $type): void
    {
        [$fillColor, $fontColor] = $this->resolveScoreColors($score, $type);

        $sheet->getStyle($cell)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($fillColor);

        $sheet->getStyle($cell)->getFont()
            ->setBold(true)
            ->getColor()->setRGB($fontColor);

        $sheet->getStyle($cell)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function resolveScoreColors(int $score, string $type): array
    {
        if ($type === 'opportunite') {
            return match (true) {
                $score >= 12 => ['1565C0', 'FFFFFF'],
                $score >= 8 => ['00897B', 'FFFFFF'],
                $score >= 4 => ['7CB342', '0F172A'],
                default => ['8E24AA', 'FFFFFF'],
            };
        }

        return match (true) {
            $score >= 12 => ['D32F2F', 'FFFFFF'],
            $score >= 8 => ['F57C00', '0F172A'],
            $score >= 4 => ['FBC02D', '0F172A'],
            default => ['2E7D32', 'FFFFFF'],
        };
    }

    private function riskActionTypeLabel(?string $type): string
    {
        return match ($type) {
            'preventive' => 'Préventive',
            'corrective' => 'Corrective',
            'control' => 'Maîtrise',
            default => '',
        };
    }

    public function cartography(Request $request)
    {
        $user = $request->user();
        $siteId = $request->filled('site_id') ? (int) $request->site_id : ($user?->site_id ? (int) $user->site_id : null);
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        $data = $this->processService->getCartographyData($siteId, $enterpriseId);
        return response()->json(['data' => $data]);
    }

    /**
     * Export or Preview Process Cartography PDF
     */
    public function exportCartographyPdf(Request $request)
    {
        $user = $request->user();
        $siteId = $request->filled('site_id') ? (int) $request->site_id : ($user?->site_id ? (int) $user->site_id : null);
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        $data = $this->processService->getCartographyData($siteId, $enterpriseId);

        $enterprise = $user?->enterprise ?? ($siteId ? \App\Models\Site::find($siteId)?->enterprise : null);
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'process_cartography')
            : null;

        $existingDoc = Document::query()
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->where(function ($q) {
                $q->where('metadata->document_kind', 'process_cartography')
                  ->orWhere('source_type', 'process_cartography');
            })
            ->latest('id')
            ->first();

        $pdf = Pdf::loadView('pdf.process-cartography', [
            'data' => $data,
            'enterprise' => $enterprise,
            'document' => $existingDoc,
            'document_code' => $existingDoc?->code,
            'document_version' => $existingDoc?->version ?? '1.0',
            'branding' => $branding,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'cartographie_processus_' . now()->format('Ymd_His') . '.pdf';

        if ($request->boolean('preview') || $request->header('Accept') === 'application/pdf') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Export Process Cartography as DOCX (Compliant with User 4-column Model)
     */
    public function exportCartographyDocx(Request $request)
    {
        $user = $request->user();
        $siteId = $request->filled('site_id') ? (int) $request->site_id : ($user?->site_id ? (int) $user->site_id : null);
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        $data = $this->processService->getCartographyData($siteId, $enterpriseId);

        $enterprise = $user?->enterprise ?? ($siteId ? \App\Models\Site::find($siteId)?->enterprise : null);

        $existingDoc = Document::query()
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->where(function ($q) {
                $q->where('metadata->document_kind', 'process_cartography')
                  ->orWhere('source_type', 'process_cartography');
            })
            ->latest('id')
            ->first();

        $generator = new \App\Services\Docx\ProcessCartographyDocxGenerator();
        $filePath = $generator->generate($data, $enterprise, [
            'code' => $existingDoc?->code,
            'version' => $existingDoc?->version ?? '1.0',
            'title' => $existingDoc?->title ?? 'Cartographie des Processus',
            'effective_date' => optional($existingDoc?->effective_date ?? now())->format('d/m/Y'),
        ]);

        $filename = 'cartographie_processus_' . now()->format('Ymd_His') . '.docx';

        return response()
            ->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Generate Process Cartography Draft Document (codified via enterprise rules)
     */
    public function generateCartographyDraft(Request $request)
    {
        $user = $request->user();
        $siteId = $request->filled('site_id') ? (int) $request->site_id : ($user?->site_id ? (int) $user->site_id : null);
        if (!$siteId) {
            $siteId = (int) (Site::where('enterprise_id', $user?->enterprise_id)->value('id') ?? 1);
        }

        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId, null, 'CART', 'Cartographie des Processus');

        $site = Site::find($siteId);
        $enterprise = $site?->enterprise ?? $user?->enterprise;
        $enterpriseName = $enterprise?->name ?? 'Système de Management de la Qualité';

        // 1. Réserver et synchroniser le document avec codification officielle par entreprise
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'process_cartography',
            'source_type' => 'process_cartography',
            'source_id' => $siteId,
            'title' => 'Cartographie des Processus - ' . $enterpriseName,
            'description' => 'Cartographie des processus et matrice des interactions générée automatiquement.',
            'created_by' => $user?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'metadata' => array_merge($generationContext['metadata'], [
                'site_id' => $siteId,
                'enterprise_name' => $enterpriseName,
            ]),
        ]);

        // 2. Générer le document avec le code et la version officiels
        $data = $this->processService->getCartographyData($siteId, (int) ($enterprise?->id ?? 0));

        $generator = new \App\Services\Docx\ProcessCartographyDocxGenerator();
        $filePath = $generator->generate($data, $enterprise, [
            'code' => $document->code,
            'version' => $document->version ?? '1.0',
            'title' => $document->title,
            'effective_date' => optional($document->effective_date ?? $document->created_at)->format('d/m/Y'),
        ]);

        // 3. Associer le fichier au document
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'document_id' => $document->id,
            'site_id' => $siteId,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'process_cartography',
            'source_type' => 'process_cartography',
            'source_id' => $siteId,
            'title' => $document->title,
            'description' => $document->description,
            'file_source_path' => $filePath,
            'created_by' => $user?->id,
            'type' => $generationContext['type'],
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }

    /**
     * Export Process Sheet as DOCX
     */
    public function exportDocx(Request $request, Process $process)
    {
        try {
            $generationContext = $this->resolveGeneratedDocumentContext($request, (int) $process->site_id, (int) $process->id);

            $process->load([
                'pilot', 'copilot', 'copilots',
                'sequences',
                'processObjectives.indicator',
                'risksOpportunities'
            ]);

            $document = app(\App\Services\DocumentSyncService::class)->upsertGeneratedProcessDocument([
                'site_id' => (int) $process->site_id,
                'process_id' => (int) $process->id,
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'process_sheet',
                'source_type' => 'process',
                'source_id' => $process->id,
                'source_updated_at' => $process->updated_at?->toISOString(),
                'title' => 'Fiche processus - ' . ($process->name ?? ('Processus ' . $process->id)),
                'description' => 'Fiche processus générée automatiquement depuis le module Processus.',
                'created_by' => Auth::id(),
                'type' => $generationContext['type'],
                'force_new' => true,
                'metadata' => $generationContext['metadata'],
            ]);

            $generator = new \App\Services\Docx\ProcessSheetGenerator();
            $filePath = $generator->generate($process, [
                'code' => $document->code,
                'version' => $document->version ?? '1.0',
                'title' => $document->title,
                'effective_date' => optional($document->effective_date ?? $document->created_at)->format('d/m/Y'),
            ]);

            if (!is_string($filePath) || !is_file($filePath)) {
                throw ValidationException::withMessages([
                    'export' => ['Le fichier DOCX n\'a pas pu être généré.'],
                ]);
            }

            // Faire une copie du fichier pour la sauvegarde pérenne dans le document
            $fileCopy = tempnam(sys_get_temp_dir(), 'process_sheet_copy_') . '.docx';
            copy($filePath, $fileCopy);

            // Synchronisation auto dans l'inventaire documentaire
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $process->site_id,
                'process_id' => (int) $process->id,
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'process_sheet',
                'source_type' => 'process',
                'source_id' => $process->id,
                'source_updated_at' => $process->updated_at?->toISOString(),
                'title' => 'Fiche processus - ' . ($process->name ?? ('Processus ' . $process->id)),
                'description' => 'Fiche processus générée automatiquement depuis le module Processus.',
                'file_source_path' => $fileCopy,
                'created_by' => Auth::id(),
                'type' => $generationContext['type'],
                'store_file' => true,
                'metadata' => $generationContext['metadata'],
            ]);

            $filename = 'Fiche_Processus_' . ($process->code ?? $process->id) . '.docx';

            return response()
                ->download($filePath, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Access-Control-Expose-Headers' => 'X-Generated-Document-Id',
                ])
                ->header('X-Generated-Document-Id', (string) $document->id)
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Erreur export DOCX processus', [
                'process_id' => $process->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export Process Sheet as PDF
     */
    public function exportPdf(Request $request, Process $process)
    {
        try {
            $generationContext = $this->resolveGeneratedDocumentContext($request, (int) $process->site_id, (int) $process->id);

            $process->load([
                'pilot', 'copilot', 'copilots',
                'site.enterprise',
                'sequences.responsibleUser',
                'processObjectives.indicator',
                'risksOpportunities'
            ]);

            $enterprise = $process->site?->enterprise ?? $request->user()?->enterprise;
            $branding = $enterprise
                ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'process')
                : null;

            $copilotNames = [];
            if ($process->copilots) {
                $copilotNames = $process->copilots->pluck('name')->filter()->values()->all();
            }
            if (empty($copilotNames) && $process->copilot?->name) {
                $copilotNames[] = $process->copilot->name;
            }

            $pdf = Pdf::loadView('pdf.process-sheet', [
                'process' => $process,
                'enterprise' => $enterprise,
                'branding' => $branding,
                'copilotNames' => $copilotNames,
                'effective_date' => now()->format('d/m/Y'),
            ])->setPaper('a4', 'portrait');

            $filename = 'Fiche_Processus_' . ($process->code ?? $process->id) . '.pdf';

            if ($request->boolean('preview') || $request->header('Accept') === 'application/pdf') {
                return $pdf->stream($filename);
            }

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            Log::error('Erreur export PDF processus', [
                'process_id' => $process->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function generateDraftDocx(Request $request, Process $process)
    {
        $generationContext = $this->resolveGeneratedDocumentContext($request, (int) $process->site_id, (int) $process->id);

        $process->load([
            'pilot', 'copilot', 'copilots',
            'sequences',
            'processObjectives.indicator',
            'risksOpportunities'
        ]);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => (int) $process->site_id,
            'process_id' => (int) $process->id,
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'process_sheet',
            'source_type' => 'process',
            'source_id' => $process->id,
            'source_updated_at' => $process->updated_at?->toISOString(),
            'title' => 'Fiche processus - ' . ($process->name ?? ('Processus ' . $process->id)),
            'description' => 'Fiche processus générée automatiquement depuis le module Processus.',
            'created_by' => Auth::id(),
            'type' => $generationContext['type'],
            'force_new' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        $generator = new \App\Services\Docx\ProcessSheetGenerator();
        $filePath = $generator->generate($process, [
            'code' => $document->code,
            'version' => $document->version ?? '1.0',
            'title' => $document->title,
            'effective_date' => optional($document->effective_date ?? $document->created_at)->format('d/m/Y'),
        ]);

        if (!is_string($filePath) || !is_file($filePath)) {
            throw ValidationException::withMessages([
                'export' => ['Le fichier DOCX n\'a pas pu être généré.'],
            ]);
        }

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'document_id' => $document->id,
            'site_id' => (int) $process->site_id,
            'process_id' => (int) $process->id,
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'process_sheet',
            'source_type' => 'process',
            'source_id' => $process->id,
            'source_updated_at' => $process->updated_at?->toISOString(),
            'title' => 'Fiche processus - ' . ($process->name ?? ('Processus ' . $process->id)),
            'description' => 'Fiche processus générée automatiquement depuis le module Processus.',
            'file_source_path' => $filePath,
            'created_by' => Auth::id(),
            'type' => $generationContext['type'],
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }

    // === SEQUENCES ===

    public function addSequence(Request $request, Process $process)
    {
        $validated = $request->validate([
            'sequence_order' => 'nullable|integer',
            'input_description' => 'nullable|string',
            'activity_description' => 'required|string',
            'sub_activities' => 'nullable|array',
            'sub_activities.*' => 'nullable|string|max:255',
            'output_description' => 'nullable|string',
            'supplier_processes' => 'nullable|array',
            'client_processes' => 'nullable|array',
            'responsible_user_id' => 'nullable|exists:users,id',
            'duration_minutes' => 'nullable|integer|min:1',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:documents,id',
        ]);

        // Auto-increment sequence_order if not provided
        if (!isset($validated['sequence_order'])) {
            $validated['sequence_order'] = $process->sequences()->max('sequence_order') + 1;
        }
        if ($this->hasSubActivitiesColumn() && array_key_exists('sub_activities', $validated)) {
            $validated['sub_activities'] = $this->normalizeSubActivities($validated['sub_activities']);
        } else {
            unset($validated['sub_activities']);
        }

        $sequence = $process->sequences()->create($validated);

        // Attach documents if provided
        if (isset($validated['document_ids'])) {
            $sequence->documents()->attach($validated['document_ids']);
        }

        $sequence->load('responsibleUser', 'documents');

        return response()->json(['data' => $sequence], 201);
    }

    private function buildSequencePayload(array $seqData, int $index): array
    {
        Log::info('[ProcessController] buildSequencePayload INPUT', ['seqData' => $seqData, 'index' => $index]);
        
        $normalizeProcesses = function ($value): array {
            if (!$value) {
                return [];
            }
            
            if (!is_array($value)) {
                return [];
            }

            $result = array_values(array_filter(array_map(function ($item) {
                if (is_string($item) || is_numeric($item)) {
                    $str = trim((string) $item);
                    return $str !== '' ? $str : null;
                }
                if (is_array($item)) {
                    $str = trim((string) ($item['name'] ?? $item['title'] ?? $item['label'] ?? ''));
                    return $str !== '' ? $str : null;
                }
                if (is_object($item)) {
                    $str = trim((string) ($item->name ?? $item->title ?? $item->label ?? ''));
                    return $str !== '' ? $str : null;
                }
                return null;
            }, $value), function($v) { return $v !== null; }));
            
            return $result;
        };

        $payload = [
            'sequence_order' => $seqData['sequence_order'] ?? ($index + 1),
            'input_description' => $seqData['input_description'] ?? $seqData['inputs'] ?? '',
            'activity_description' => $seqData['activity_description'] ?? $seqData['activities'] ?? '',
            'output_description' => $seqData['output_description'] ?? $seqData['outputs'] ?? '',
            'responsible_user_id' => $seqData['responsible_user_id'] ?? null,
            'duration_minutes' => $seqData['duration_minutes'] ?? null,
        ];

        if ($this->hasSubActivitiesColumn()) {
            $payload['sub_activities'] = $this->normalizeSubActivities(
                $seqData['sub_activities'] ?? $seqData['subActivities'] ?? []
            );
        }

        if (Schema::hasColumn('process_sequences', 'supplier_processes')) {
            $supplierData = $seqData['supplier_processes'] ?? $seqData['supplierProcesses'] ?? [];
            $payload['supplier_processes'] = $normalizeProcesses($supplierData);
        }

        if (Schema::hasColumn('process_sequences', 'client_processes')) {
            $clientData = $seqData['client_processes'] ?? $seqData['clientProcesses'] ?? [];
            $payload['client_processes'] = $normalizeProcesses($clientData);
        }

        Log::info('[ProcessController] buildSequencePayload OUTPUT', ['payload' => $payload]);
        return $payload;
    }

    private function normalizeSubActivities($value): array
    {
        if (!$value || !is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            $text = trim((string) $item);
            return $text !== '' ? $text : null;
        }, $value), fn ($item) => $item !== null));
    }

    private function hasSubActivitiesColumn(): bool
    {
        return Schema::hasColumn('process_sequences', 'sub_activities');
    }

    private function ensureSubActivitiesColumnIfProvided(array $sequences): void
    {
        $hasIncomingSubActivities = collect($sequences)->contains(function ($sequence) {
            if (!is_array($sequence)) {
                return false;
            }

            $raw = $sequence['sub_activities'] ?? $sequence['subActivities'] ?? null;
            return is_array($raw) && count(array_filter($raw, function ($item) {
                return trim((string) $item) !== '';
            })) > 0;
        });

        if ($hasIncomingSubActivities && !$this->hasSubActivitiesColumn()) {
            throw ValidationException::withMessages([
                'sequences' => 'La colonne sub_activities est absente de la base. Exécutez les migrations pour process_sequences.',
            ]);
        }
    }

    public function updateSequence(Request $request, Process $process, $sequenceId)
    {
        $sequence = $process->sequences()->findOrFail($sequenceId);

        $validated = $request->validate([
            'sequence_order' => 'nullable|integer',
            'input_description' => 'nullable|string',
            'activity_description' => 'sometimes|string',
            'sub_activities' => 'nullable|array',
            'sub_activities.*' => 'nullable|string|max:255',
            'output_description' => 'nullable|string',
            'supplier_processes' => 'nullable|array',
            'client_processes' => 'nullable|array',
            'responsible_user_id' => 'nullable|exists:users,id',
            'duration_minutes' => 'nullable|integer|min:1',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:documents,id',
        ]);
        if ($this->hasSubActivitiesColumn() && array_key_exists('sub_activities', $validated)) {
            $validated['sub_activities'] = $this->normalizeSubActivities($validated['sub_activities']);
        } else {
            unset($validated['sub_activities']);
        }

        $sequence->update($validated);

        // Sync documents if provided
        if (isset($validated['document_ids'])) {
            $sequence->documents()->sync($validated['document_ids']);
        }

        $sequence->load('responsibleUser', 'documents');

        return response()->json(['data' => $sequence]);
    }

    public function deleteSequence(Process $process, $sequenceId)
    {
        $sequence = $process->sequences()->findOrFail($sequenceId);
        $sequence->delete();

        return response()->json(['message' => 'Séquence supprimée'], 204);
    }

    // === VERSIONS ===

    public function getVersions(Process $process)
    {
        $versions = $process->versions()
            ->with(['author', 'verifier', 'approver'])
            ->get();

        return response()->json(['data' => $versions]);
    }

    public function createVersion(Request $request, Process $process)
    {
        $validated = $request->validate([
            'changes_description' => 'required|string',
            'verifier_user_id' => 'nullable|exists:users,id',
            'approver_user_id' => 'nullable|exists:users,id',
        ]);

        // Get current version
        $currentVersion = $process->currentVersion;
        
        // Increment version number
        if ($currentVersion) {
            $parts = explode('.', $currentVersion->version_number);
            $major = (int) $parts[0];
            $minor = isset($parts[1]) ? (int) $parts[1] : 0;
            $newVersionNumber = $major . '.' . ($minor + 1);
            
            // Set current version as not current
            $currentVersion->update(['is_current' => false]);
        } else {
            $newVersionNumber = '1.0';
        }

        $version = $process->versions()->create([
            'version_number' => $newVersionNumber,
            'version_date' => now(),
            'author_user_id' => Auth::id() ?? $process->pilot_id ?? 1,
            'verifier_user_id' => $validated['verifier_user_id'] ?? null,
            'approver_user_id' => $validated['approver_user_id'] ?? null,
            'status' => 'draft',
            'changes_description' => $validated['changes_description'],
            'is_current' => true,
        ]);

        $version->load(['author', 'verifier', 'approver']);

        return response()->json(['data' => $version], 201);
    }

    public function verifyVersion(Process $process, $versionId)
    {
        $version = $process->versions()->findOrFail($versionId);
        
        if (!$version->canBeVerified()) {
            return response()->json(['message' => 'Cette version ne peut pas être vérifiée'], 400);
        }

        $version->verify(Auth::id());

        // TODO: Send notification to approver

        return response()->json(['data' => $version]);
    }

    public function approveVersion(Process $process, $versionId)
    {
        $version = $process->versions()->findOrFail($versionId);
        
        if (!$version->canBeApproved()) {
            return response()->json(['message' => 'Cette version ne peut pas être approuvée'], 400);
        }

        $version->approve(Auth::id());
        
        // TODO: Send notifications

        return response()->json(['data' => $version]);
    }

    // === OBJECTIVES ===

    /**
     * Contrat C8 : Liste des objectifs d'un processus avec taux actuel calculé côté serveur selon §8
     */
    public function getObjectives(Process $process)
    {
        $calcService = app(ObjectiveCalculationService::class);
        $currentMonth = (int) date('n');
        $objectives = $process->processObjectives()->with('indicator')->get();

        $data = $objectives->map(function ($obj) use ($calcService, $currentMonth) {
            $periods = is_array($obj->period_realizations) ? $obj->period_realizations : [];
            $currentRate = $calcService->calculateCurrentRate(
                $periods,
                $obj->measurement_frequency ?? 'monthly',
                $currentMonth
            );

            $arr = $obj->toArray();
            $arr['current_achievement_rate'] = $currentRate;
            $arr['taux_actuel'] = $currentRate;
            return $arr;
        });

        return response()->json([
            'data' => $data,
            'process_id' => $process->id,
            'process_name' => $process->name,
        ]);
    }

    public function addObjective(Request $request, Process $process)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'strategic_axis' => 'nullable|string|max:255',
            'strategic_axes' => 'nullable|array',
            'strategic_axes.*' => 'nullable|string|max:255',
            'applicable_norms' => 'nullable|array',
            'applicable_norms.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'calculation_mode' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'target_date' => 'nullable|date',
            'measurement_frequency' => 'nullable|in:monthly,quarterly,semiannual,annual',
            'indicator_id' => 'nullable|exists:process_indicators,id',
            'indicator_name' => 'nullable|string|max:255|required_without:indicator_id',
            'period_realizations' => 'nullable|array',
            'period_realizations.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') return;
                    if (is_string($value) && in_array(strtoupper(trim($value)), ['NA', 'N/A'], true)) return;
                    if (is_array($value) && (!empty($value['is_na']) || in_array(strtoupper(trim($value['value'] ?? '')), ['NA', 'N/A'], true))) return;
                    $num = is_array($value) ? ($value['value'] ?? null) : $value;
                    if (!is_numeric($num)) {
                        $fail("La valeur mensuelle doit être un nombre valide ou N/A.");
                        return;
                    }
                    if ((float) $num < 0 || (float) $num > 100) {
                        $fail("Le taux mensuel ne peut pas dépasser 100 % (REQ-6.2-02).");
                    }
                },
            ],
            'notes' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'planned_actions' => 'nullable|array',
            'planned_actions.*.title' => 'required_with:planned_actions|string|max:255',
            'planned_actions.*.responsible' => 'nullable|string|max:255',
            'planned_actions.*.responsible_user_id' => 'nullable|integer|exists:users,id',
            'planned_actions.*.involved_user_ids' => 'nullable|array',
            'planned_actions.*.involved_user_ids.*' => 'integer|exists:users,id',
            'planned_actions.*.progress_rate' => 'nullable|integer|min:0|max:100',
            'planned_actions.*.start_date' => 'nullable|date',
            'planned_actions.*.end_date' => 'nullable|date',
            'planned_actions.*.due_date' => 'nullable|date',
            'planned_actions.*.status' => 'nullable|in:a_faire,en_cours,terminee',
            'responsibles' => 'nullable|string',
            'special_resources' => 'nullable|string',
        ]);

        if (empty($validated['indicator_name']) && !empty($validated['indicator_id'])) {
            $indicator = ProcessIndicator::find($validated['indicator_id']);
            if ($indicator) {
                $validated['indicator_name'] = $indicator->name;
            }
        }
        $validated = $this->normalizeObjectiveAxes($validated);
        $validated = $this->normalizeObjectiveRealizations($validated);
        $validated = $this->normalizeObjectivePlannedActions($validated, $process);
        $validated = $this->filterObjectivePayloadByExistingColumns($validated);

        $objective = $process->processObjectives()->create($validated);
        $this->syncObjectiveActions($process, $objective);
        $objective->refresh();
        $objective->load('indicator');

        return response()->json(['data' => $objective], 201);
    }

    public function updateObjective(Request $request, Process $process, $objectiveId)
    {
        $objective = $process->processObjectives()->findOrFail($objectiveId);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'strategic_axis' => 'nullable|string|max:255',
            'strategic_axes' => 'nullable|array',
            'strategic_axes.*' => 'nullable|string|max:255',
            'applicable_norms' => 'nullable|array',
            'applicable_norms.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'calculation_mode' => 'nullable|string',
            'target_value' => 'nullable|numeric',
            'target_date' => 'nullable|date',
            'measurement_frequency' => 'nullable|in:monthly,quarterly,semiannual,annual',
            'indicator_id' => 'nullable|exists:process_indicators,id',
            'indicator_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:not_started,in_progress,achieved,failed',
            'achievement_percentage' => 'nullable|integer|min:0|max:100',
            'period_realizations' => 'nullable|array',
            'period_realizations.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') return;
                    if (is_string($value) && in_array(strtoupper(trim($value)), ['NA', 'N/A'], true)) return;
                    if (is_array($value) && (!empty($value['is_na']) || in_array(strtoupper(trim($value['value'] ?? '')), ['NA', 'N/A'], true))) return;
                    $num = is_array($value) ? ($value['value'] ?? null) : $value;
                    if (!is_numeric($num)) {
                        $fail("La valeur mensuelle doit être un nombre valide ou N/A.");
                        return;
                    }
                    if ((float) $num < 0 || (float) $num > 100) {
                        $fail("Le taux mensuel ne peut pas dépasser 100 % (REQ-6.2-02).");
                    }
                },
            ],
            'notes' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'planned_actions' => 'nullable|array',
            'planned_actions.*.title' => 'required_with:planned_actions|string|max:255',
            'planned_actions.*.responsible' => 'nullable|string|max:255',
            'planned_actions.*.responsible_user_id' => 'nullable|integer|exists:users,id',
            'planned_actions.*.involved_user_ids' => 'nullable|array',
            'planned_actions.*.involved_user_ids.*' => 'integer|exists:users,id',
            'planned_actions.*.progress_rate' => 'nullable|integer|min:0|max:100',
            'planned_actions.*.start_date' => 'nullable|date',
            'planned_actions.*.end_date' => 'nullable|date',
            'planned_actions.*.due_date' => 'nullable|date',
            'planned_actions.*.status' => 'nullable|in:a_faire,en_cours,terminee',
            'responsibles' => 'nullable|string',
            'special_resources' => 'nullable|string',
        ]);

        // Verrouillage temporel mensuel (REQ-6.2-06)
        if (array_key_exists('period_realizations', $validated)) {
            $existing = is_array($objective->period_realizations) ? $objective->period_realizations : [];
            $user = $request->user();
            $calcService = app(ObjectiveCalculationService::class);
            $year = (int) now()->format('Y');

            for ($m = 1; $m <= 12; $m++) {
                $idx = $m - 1;
                $oldVal = $existing[$idx] ?? null;
                $newVal = $validated['period_realizations'][$idx] ?? null;

                // Si la valeur a changé pour un mois verrouillé
                if ($oldVal !== $newVal && $calcService->isMonthLocked($m, $year, $user)) {
                    return response()->json([
                        'message' => "Le mois {$m} est verrouillé car il est antérieur au mois en cours (REQ-6.2-06).",
                        'errors' => ['period_realizations' => ["Le mois {$m} est verrouillé en lecture seule."]]
                    ], 422);
                }
            }
        }

        $validated = $this->normalizeObjectiveAxes($validated);
        $validated = $this->normalizeObjectiveRealizations($validated);
        $validated = $this->normalizeObjectivePlannedActions($validated, $process);
        $validated = $this->filterObjectivePayloadByExistingColumns($validated);

        $objective->update($validated);
        $this->syncObjectiveActions($process, $objective);
        $objective->refresh();
        $objective->load('indicator');

        return response()->json(['data' => $objective]);
    }

    public function systemObjectivesSummary(Request $request)
    {
        $user = $request->user();
        $siteId = $request->integer('site_id');
        $query = ProcessObjective::with(['process.site', 'indicator']);

        if ($siteId > 0) {
            $query->whereHas('process', fn ($q) => $q->where('site_id', $siteId));
        } elseif ($user && $user->user_type !== 'super_admin' && $user->enterprise_id) {
            $query->whereHas('process.site', fn ($q) => $q->where('enterprise_id', $user->enterprise_id));
        }

        $currentMonth = $request->filled('month') ? (int) $request->integer('month') : null;
        $objectives = $query->get();
        $calcService = app(ObjectiveCalculationService::class);
        $summary = $calcService->calculateSystemSummary($objectives, $currentMonth);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    public function deleteObjective(Process $process, $objectiveId)
    {
        $objective = $process->processObjectives()->findOrFail($objectiveId);
        $objective->delete();

        return response()->json(['message' => 'Objectif supprimé'], 204);
    }

    private function filterObjectivePayloadByExistingColumns(array $payload): array
    {
        $columns = Schema::getColumnListing('process_objectives');

        return array_filter(
            $payload,
            fn ($key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function normalizeObjectiveRealizations(array $payload): array
    {
        if (!array_key_exists('period_realizations', $payload)) {
            return $payload;
        }

        $frequency = $payload['measurement_frequency'] ?? 'monthly';
        $counts = [
            'monthly' => 12,
            'quarterly' => 4,
            'semiannual' => 2,
            'annual' => 1,
        ];
        $expectedCount = $counts[$frequency] ?? 12;

        $current = is_array($payload['period_realizations']) ? $payload['period_realizations'] : [];

        // Preserve existing values, supporting numeric, null, and 'N/A'
        $normalized = [];
        for ($i = 0; $i < $expectedCount; $i++) {
            $val = $current[$i] ?? null;
            if ($val === null || $val === '') {
                $normalized[$i] = null;
            } elseif (is_string($val) && in_array(strtoupper(trim($val)), ['NA', 'N/A'], true)) {
                $normalized[$i] = 'N/A';
            } elseif (is_array($val) && (!empty($val['is_na']) || in_array(strtoupper(trim($val['value'] ?? '')), ['NA', 'N/A'], true))) {
                $normalized[$i] = 'N/A';
            } else {
                $num = is_array($val) ? ($val['value'] ?? null) : $val;
                $normalized[$i] = is_numeric($num) ? min(100.0, max(0.0, (float) $num)) : null;
            }
        }

        $payload['period_realizations'] = $normalized;

        return $payload;
    }

    private function normalizeObjectivePlannedActions(array $payload, Process $process): array
    {
        if (!array_key_exists('planned_actions', $payload) || !is_array($payload['planned_actions'])) {
            return $payload;
        }

        $normalized = [];
        foreach ($payload['planned_actions'] as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $status = in_array(($item['status'] ?? ''), ['a_faire', 'en_cours', 'terminee'], true)
                ? $item['status']
                : 'a_faire';

            $rate = isset($item['progress_rate']) ? (int) $item['progress_rate'] : null;
            if ($status === 'a_faire') {
                $rate = 0;
            } elseif ($status === 'terminee') {
                $rate = 100;
            } else {
                $rate = $rate === null ? 1 : max(1, min(99, $rate));
            }

            $responsibleId = isset($item['responsible_user_id']) ? (int) $item['responsible_user_id'] : 0;
            $involvedIds = collect((array) ($item['involved_user_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $dueDate = !empty($item['due_date']) ? Carbon::parse((string) $item['due_date'])->format('Y-m-d') : null;
            $startDate = !empty($item['start_date']) ? Carbon::parse((string) $item['start_date'])->format('Y-m-d') : null;
            $endDate = !empty($item['end_date']) ? Carbon::parse((string) $item['end_date'])->format('Y-m-d') : null;
            if ($status === 'terminee' && !$endDate) {
                $endDate = now()->format('Y-m-d');
            }

            $trackingAvailable = false;
            if ($dueDate) {
                $trackingAvailable = $this->workingDaysService->isInWindow(
                    Carbon::parse($dueDate),
                    $this->mapObjectiveFrequencyToTrackingFrequency((string) ($payload['measurement_frequency'] ?? 'monthly')),
                    Carbon::today(),
                    (int) ($process->enterprise_id ?? 0)
                );
            }

            $normalized[] = [
                'title' => $title,
                'responsible_user_id' => $responsibleId > 0 ? $responsibleId : null,
                'responsible' => trim((string) ($item['responsible'] ?? '')),
                'involved_user_ids' => $involvedIds,
                'due_date' => $dueDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'progress_rate' => $rate,
                'tracking_available' => $trackingAvailable,
                'linked_action_id' => isset($item['linked_action_id']) ? (int) $item['linked_action_id'] : null,
                'sync_key' => $this->buildObjectiveActionSyncKey($index, $title, $dueDate),
            ];
        }

        $payload['planned_actions'] = $normalized;

        if ($normalized !== []) {
            $avg = (int) round(collect($normalized)->avg(fn ($row) => (int) ($row['progress_rate'] ?? 0)) ?? 0);
            $payload['achievement_percentage'] = max(0, min(100, $avg));
            if (($payload['status'] ?? null) !== 'failed') {
                $payload['status'] = $avg >= 100 ? 'achieved' : ($avg > 0 ? 'in_progress' : 'not_started');
            }
        }

        return $payload;
    }

    private function syncObjectiveActions(Process $process, ProcessObjective $objective): void
    {
        $plannedActions = is_array($objective->planned_actions) ? $objective->planned_actions : [];
        $existing = Action::query()
            ->where('source_type', 'process_objective')
            ->where('source_id', (int) $objective->id)
            ->where('process_id', (int) $process->id)
            ->orderBy('id')
            ->get()
            ->values();

        $keptActionIds = [];
        foreach ($plannedActions as $index => $plannedAction) {
            if (!is_array($plannedAction) || trim((string) ($plannedAction['title'] ?? '')) === '') {
                continue;
            }

            $responsibleId = (int) ($plannedAction['responsible_user_id'] ?? 0);
            $involvedIds = collect((array) ($plannedAction['involved_user_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $status = (string) ($plannedAction['status'] ?? 'a_faire');
            $progressRate = (int) ($plannedAction['progress_rate'] ?? 0);

            $actionPayload = [
                'site_id' => (int) $process->site_id,
                'process_id' => (int) $process->id,
                'enterprise_id' => (int) ($process->enterprise_id ?? 0),
                'type' => 'improvement',
                'title' => trim((string) $plannedAction['title']),
                'description' => trim((string) ($objective->action_plan ?: $objective->description ?: 'Action liée à un objectif')),
                'responsible_id' => $responsibleId > 0 ? $responsibleId : null,
                'deadline' => !empty($plannedAction['due_date']) ? $plannedAction['due_date'] : null,
                'start_date' => !empty($plannedAction['start_date']) ? $plannedAction['start_date'] : null,
                'completion_date' => !empty($plannedAction['end_date']) ? $plannedAction['end_date'] : null,
                'status' => $status === 'terminee' ? 'completed' : ($status === 'en_cours' ? 'in_progress' : 'planned'),
                'progress' => $progressRate,
                'progress_rate' => $progressRate,
                'priority' => 'medium',
                'source' => null,
                'source_type' => 'process_objective',
                'source_id' => (int) $objective->id,
                'initiator_id' => auth()->id(),
            ];
            /** @var Action|null $existingAction */
            $existingAction = $existing->get($index);
            if ($existingAction) {
                $existingAction->update($actionPayload);
                $actionModel = $existingAction;
            } else {
                $actionModel = Action::query()->create($actionPayload);
            }

            if (Schema::hasColumn('actions', 'stakeholders_user_ids')) {
                $actionModel->forceFill(['stakeholders_user_ids' => $involvedIds])->save();
            }

            $keptActionIds[] = (int) $actionModel->id;

            $plannedActions[$index]['linked_action_id'] = (int) $actionModel->id;
            $plannedActions[$index]['tracking_available'] = !empty($plannedActions[$index]['due_date'])
                ? $this->workingDaysService->isInWindow(
                    Carbon::parse((string) $plannedActions[$index]['due_date']),
                    $this->mapObjectiveFrequencyToTrackingFrequency((string) ($objective->measurement_frequency ?? 'monthly')),
                    Carbon::today(),
                    (int) ($process->enterprise_id ?? 0)
                )
                : false;
        }

        if ($existing->isNotEmpty()) {
            Action::query()
                ->whereIn('id', $existing->pluck('id')->all())
                ->when(
                    $keptActionIds !== [],
                    fn ($query) => $query->whereNotIn('id', $keptActionIds)
                )
                ->delete();
        }

        $objective->planned_actions = $plannedActions;
        $objective->save();
    }

    private function mapObjectiveFrequencyToTrackingFrequency(string $frequency): string
    {
        return match ($frequency) {
            'annual' => 'annuelle',
            'semiannual' => 'semestrielle',
            'quarterly' => 'trimestrielle',
            'monthly' => 'mensuelle',
            default => 'ponctuelle',
        };
    }

    private function buildObjectiveActionSyncKey(int $index, string $title, ?string $dueDate): string
    {
        return sha1($index . '|' . mb_strtolower(trim($title)) . '|' . ($dueDate ?? ''));
    }

    private function normalizeObjectiveAxes(array $payload): array
    {
        if (!array_key_exists('strategic_axes', $payload) && !array_key_exists('strategic_axis', $payload)) {
            return $payload;
        }

        $axes = $payload['strategic_axes'] ?? [];
        if (!is_array($axes) || count($axes) === 0) {
            $singleAxis = trim((string) ($payload['strategic_axis'] ?? ''));
            $axes = $singleAxis !== '' ? [$singleAxis] : [];
        }

        $payload['strategic_axes'] = array_values(array_filter(
            array_map(fn ($axis) => trim((string) $axis), $axes),
            fn ($axis) => $axis !== ''
        ));

        return $payload;
    }

    // === WORKFLOW ===

    /**
     * Get statistics for processes dashboard
     */
    public function statistics(Request $request)
    {
        $query = Process::query();

        // Filter by site if specified
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Statistics by status
        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Statistics by category/type
        $byCategory = (clone $query)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category');

        // Statistics by level
        $byLevel = (clone $query)
            ->select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->get()
            ->pluck('count', 'level');

        // Review statistics
        $reviewDue = (clone $query)->reviewDue()->count();
        $reviewUpcoming = (clone $query)
            ->whereBetween('next_review_at', [now(), now()->addDays(30)])
            ->count();

        return response()->json([
            'data' => [
                'by_status' => [
                    'draft' => $byStatus['draft'] ?? 0,
                    'in_review' => $byStatus['in_review'] ?? 0,
                    'validated' => $byStatus['validated'] ?? 0,
                    'active' => $byStatus['active'] ?? 0,
                    'obsolete' => $byStatus['obsolete'] ?? 0,
                ],
                'by_category' => [
                    'pilotage' => $byCategory['pilotage'] ?? 0,
                    'support' => $byCategory['support'] ?? 0,
                    'operationnel' => $byCategory['operationnel'] ?? 0,
                    'amelioration' => $byCategory['amelioration'] ?? 0,
                ],
                'by_level' => [
                    '1' => $byLevel[1] ?? 0,
                    '2' => $byLevel[2] ?? 0,
                    '3' => $byLevel[3] ?? 0,
                ],
                'reviews' => [
                    'due' => $reviewDue,
                    'upcoming' => $reviewUpcoming,
                ],
                'total' => $query->count(),
            ],
        ]);
    }

    /**
     * Verify a process (draft → in_review)
     */
    public function verify(Request $request, Process $process)
    {
        $this->authorize('verify', $process);

        if ($process->status !== 'draft') {
            return response()->json([
                'message' => 'Seuls les processus en brouillon peuvent être vérifiés',
            ], 400);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $process->update([
            'status' => 'in_review',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Log the verification
        activity()
            ->performedOn($process)
            ->causedBy(Auth::user())
            ->withProperties(['comment' => $validated['comment'] ?? null])
            ->log('Process verified and moved to review');

        return response()->json([
            'data' => new ProcessResource($process->fresh()),
            'message' => 'Processus vérifié et passé en révision',
        ]);
    }

    /**
     * Validate a process (in_review → active)
     */
    public function validateProcess(Request $request, Process $process)
    {
        $this->authorize('validate', $process);

        if ($process->status !== 'in_review') {
            return response()->json([
                'message' => 'Seuls les processus en révision peuvent être validés',
            ], 400);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $process->update([
            'status' => 'active',
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        // Set next review date if not set
        if (!$process->next_review_at && $process->review_frequency_months) {
            $process->update([
                'next_review_at' => now()->addMonths($process->review_frequency_months),
            ]);
        }

        // Log the validation
        activity()
            ->performedOn($process)
            ->causedBy(Auth::user())
            ->withProperties(['comment' => $validated['comment'] ?? null])
            ->log('Process validated and activated');

        return response()->json([
            'data' => new ProcessResource($process->fresh()),
            'message' => 'Processus validé et activé',
        ]);
    }

    /**
     * Reject a process and return to draft
     */
    public function reject(Request $request, Process $process)
    {
        $this->authorize('reject', $process);

        if (!in_array($process->status, ['in_review', 'validated'])) {
            return response()->json([
                'message' => 'Ce processus ne peut pas être rejeté',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $previousStatus = $process->status;

        $process->update([
            'status' => 'draft',
            'verified_by' => null,
            'verified_at' => null,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        // Log the rejection
        activity()
            ->performedOn($process)
            ->causedBy(Auth::user())
            ->withProperties([
                'reason' => $validated['reason'],
                'previous_status' => $previousStatus,
            ])
            ->log('Process rejected and returned to draft');

        return response()->json([
            'data' => new ProcessResource($process->fresh()),
            'message' => 'Processus rejeté et retourné en brouillon',
        ]);
    }
}
