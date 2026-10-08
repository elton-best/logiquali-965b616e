<?php

namespace App\Modules\Support\Controllers;

use App\Models\Process;
use App\Models\Site;

use App\Http\Controllers\Controller;
use App\Services\DocumentTypeConfigurationService;
use App\Services\DocumentTypeConfigurationVisibilityService;
use App\Services\CodeGenerationService;
use App\Models\DocumentTypeConfiguration;
use App\Models\User;
use App\Notifications\NomenclaturePropagationSuggestionNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentTypeConfigurationController extends Controller
{
    public function __construct(
        private DocumentTypeConfigurationService $configService,
        private CodeGenerationService $codeService,
        private DocumentTypeConfigurationVisibilityService $visibilityService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:support.documents.configure_nomenclature')->except(['index', 'show', 'previewCode', 'visibilityStats', 'advancedFilters']);
        $this->middleware('permission:support.documents.read')->only(['index', 'show', 'visibilityStats', 'advancedFilters']);
    }

    /**
     * Liste toutes les configurations de types de documents
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = $request->only([
            'site_id', 'enterprise_id', 'document_type_id', 
            'is_active', 'search', 'scope'
        ]);

        $configurations = $this->visibilityService->getVisibleConfigurations($user, $filters);

        return response()->json([
            'data' => $configurations->map(function ($config) use ($user) {
                $payload = $this->transformConfiguration($config);
                $payload['documents_count'] = $config->documents()->count();
                $payload['can_edit'] = $this->visibilityService->canEdit($user, $config);
                $payload['scope_label'] = $this->getScopeLabel($config);
                return $payload;
            }),
        ]);
    }

    private function getScopeLabel(DocumentTypeConfiguration $config): string
    {
        if (!$config->enterprise_id && !$config->site_id) {
            return 'Global';
        }
        if ($config->enterprise_id && !$config->site_id) {
            return 'Entreprise';
        }
        return 'Site';
    }

    /**
     * Affiche une configuration spécifique
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        $config = DocumentTypeConfiguration::with('structureParts')
            ->where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        return response()->json([
            'data' => array_merge(
                $this->transformConfiguration($config),
                [
                    'validation' => $config->validateStructure(),
                    'documents_count' => $config->documents()->count(),
                ]
            ),
        ]);
    }

    /**
     * Crée une nouvelle configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $validated = $request->validate([
            'type_label' => 'required|string|max:100',
            'type_code' => 'required|string|max:10',
            'scope' => 'required|in:enterprise,site',
            'site_id' => 'nullable|exists:sites,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'code_structure' => 'required|array|min:1',
            'code_structure.*.part_type' => 'required|in:type_code,process_code,year,month,sequence,separator,custom,site_code',
            'code_structure.*.order' => 'nullable|integer|min:0',
            'code_structure.*.length' => 'nullable|integer|min:1|max:10',
            'code_structure.*.separator' => 'nullable|string|max:5',
            'code_structure.*.custom_value' => 'nullable|string|max:50',
            'code_structure.*.sequence_scope' => 'nullable|in:global,by_type,by_type_process,by_type_year,by_type_process_year,by_type_process_year_month',
        ]);

        $user = $request->user();
        $validated = $this->normalizeConfigurationPayload($validated);
        $validated['enterprise_id'] = $user->enterprise_id;

        // Si scope = site et pas de site_id fourni, utiliser le site de l'utilisateur
        if ($validated['scope'] === 'site' && !isset($validated['site_id'])) {
            $validated['site_id'] = $user->site_id;
        }

        $config = $this->configService->createConfiguration($validated);

        return response()->json([
            'message' => 'Configuration créée avec succès',
            'data' => $this->transformConfiguration($config->load('structureParts')),
        ], 201);
    }

    /**
     * Met à jour une configuration existante
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $validated = $request->validate([
            'type_label' => 'nullable|string|max:100',
            'type_code' => 'nullable|string|max:10',
            'scope' => 'sometimes|in:enterprise,site',
            'site_id' => 'nullable|exists:sites,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'code_structure' => 'sometimes|array|min:2',
            'code_structure.*.part_type' => 'required|in:type_code,process_code,year,month,sequence,separator,custom,site_code',
            'code_structure.*.order' => 'nullable|integer|min:0',
            'code_structure.*.length' => 'nullable|integer|min:1|max:10',
            'code_structure.*.separator' => 'nullable|string|max:5',
            'code_structure.*.custom_value' => 'nullable|string|max:50',
            'code_structure.*.sequence_scope' => 'nullable|in:global,by_type,by_type_process,by_type_year,by_type_process_year,by_type_process_year_month',
        ]);

        $validated = $this->normalizeConfigurationPayload($validated);
        $config = $this->configService->updateConfiguration($id, $validated);
        $this->notifyOtherSitesForPropagation($config, $request->user());

        return response()->json([
            'message' => 'Configuration mise à jour avec succès',
            'data' => $this->transformConfiguration($config),
        ]);
    }

    /**
     * Supprime une configuration
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $this->configService->deleteConfiguration($id);

        return response()->json([
            'message' => 'Configuration supprimée avec succès',
        ]);
    }

    /**
     * Prévisualise un code selon une configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_code' => 'required|string|max:10',
            'code_structure' => 'required|array|min:1',
            'code_structure.*.part_type' => 'required|in:type_code,process_code,year,month,sequence,separator,custom,site_code',
            'code_structure.*.order' => 'nullable|integer|min:0',
            'code_structure.*.length' => 'nullable|integer|min:1|max:10',
            'code_structure.*.separator' => 'nullable|string|max:5',
            'code_structure.*.custom_value' => 'nullable|string|max:50',
            'code_structure.*.sequence_scope' => 'nullable|in:global,by_type,by_type_process,by_type_year,by_type_process_year,by_type_process_year_month',
            'process_id' => 'nullable|exists:processes,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'day' => 'nullable|integer|min:1|max:31',
            'free_text' => 'nullable|array',
        ]);

        $context = [
            'site_id' => $request->user()->site_id,
            'process_id' => $validated['process_id'] ?? null,
            'year' => $validated['year'] ?? date('Y'),
            'month' => $validated['month'] ?? date('m'),
            'day' => $validated['day'] ?? date('d'),
            'free_text' => $validated['free_text'] ?? [],
        ];

        $processAbbrev = 'PRC';
        if (!empty($context['process_id'])) {
            $process = \App\Models\Process::find($context['process_id']);
            if ($process) {
                if (!empty($process->abbreviation)) {
                    $processAbbrev = strtoupper($process->abbreviation);
                } else {
                    $title = $process->title ?? $process->name ?? '';
                    if (!empty(trim($title))) {
                        $cleanTitle = preg_replace('/[^A-Za-z0-9\s]/', '', \Illuminate\Support\Str::ascii($title));
                        $words = array_values(array_filter(explode(' ', strtoupper($cleanTitle))));
                        if (count($words) >= 3) {
                            $processAbbrev = substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
                        } elseif (count($words) === 2) {
                            $processAbbrev = substr($words[0], 0, 2) . substr($words[1], 0, 1);
                        } else {
                            $processAbbrev = substr(str_replace(' ', '', strtoupper($cleanTitle)), 0, 3);
                        }
                    }
                }
            }
        }

        $normalized = $this->normalizeConfigurationPayload($validated);
        $previewParts = $normalized['structure_parts'] ?? [];
        $segments = [];
        foreach ($previewParts as $part) {
            $type = $part['part_type'] ?? '';
            $segments[] = match ($type) {
                'fixed_abbreviation' => strtoupper((string) $normalized['abbreviation']),
                'process_abbreviation' => $processAbbrev,
                'year' => (string) $context['year'],
                'month' => str_pad((string) $context['month'], 2, '0', STR_PAD_LEFT),
                'day' => str_pad((string) $context['day'], 2, '0', STR_PAD_LEFT),
                'site_code' => 'SITE',
                'free_text' => (string) ($part['default_value'] ?? 'TXT'),
                'sequence' => str_repeat('0', (int) ($part['part_length'] ?? 3)),
                default => '',
            };
            if (!empty($part['separator_after'])) {
                $segments[] = (string) $part['separator_after'];
            }
        }
        $code = rtrim(implode('', $segments), '-_./');

        return response()->json([
            'data' => [
                'code' => $code,
                'preview' => $code,
                'structure_breakdown' => [],
                'context' => $context,
            ],
        ]);
    }

    /**
     * Duplique une configuration existante
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $validated = $request->validate([
            'type_label' => 'nullable|string|max:100',
            'type_code' => 'nullable|string|max:10',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        $normalized = $this->normalizeConfigurationPayload($validated);
        $config = $this->configService->duplicateConfiguration($id, $normalized);

        return response()->json([
            'message' => 'Configuration dupliquée avec succès',
            'data' => $this->transformConfiguration($config),
        ], 201);
    }

    /**
     * Valide une structure de code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateStructure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_structure' => 'required|array|min:1',
            'code_structure.*.part_type' => 'required|in:type_code,process_code,year,month,sequence,separator,custom,site_code',
            'code_structure.*.order' => 'nullable|integer|min:0',
            'code_structure.*.length' => 'nullable|integer|min:1|max:10',
            'code_structure.*.separator' => 'nullable|string|max:5',
            'code_structure.*.custom_value' => 'nullable|string|max:50',
            'code_structure.*.sequence_scope' => 'nullable|in:global,by_type,by_type_process,by_type_year,by_type_process_year,by_type_process_year_month',
        ]);

        $normalized = $this->normalizeConfigurationPayload($validated);
        $parts = $normalized['structure_parts'] ?? [];
        $validation = $this->configService->validateStructure($parts);

        return response()->json([
            'data' => $validation,
        ]);
    }

    private function normalizeConfigurationPayload(array $payload): array
    {
        if (!empty($payload['type_code']) && empty($payload['abbreviation'])) {
            $payload['abbreviation'] = strtoupper((string) $payload['type_code']);
        }
        if (!empty($payload['type_label']) && empty($payload['name'])) {
            $payload['name'] = (string) $payload['type_label'];
        }
        if (!isset($payload['abbreviation_length']) && !empty($payload['abbreviation'])) {
            $payload['abbreviation_length'] = strlen((string) $payload['abbreviation']);
        }

        if (!empty($payload['code_structure']) && empty($payload['structure_parts'])) {
            $payload['structure_parts'] = array_map(function (array $part, int $idx): array {
                $partTypeMap = [
                    'type_code' => 'fixed_abbreviation',
                    'process_code' => 'process_abbreviation',
                    'year' => 'year',
                    'month' => 'month',
                    'sequence' => 'sequence',
                    'separator' => 'free_text',
                    'custom' => 'free_text',
                    'site_code' => 'site_code',
                ];
                $mappedType = $partTypeMap[$part['part_type'] ?? 'custom'] ?? 'free_text';

                return [
                    'part_order' => (int) ($part['order'] ?? ($idx + 1)),
                    'part_name' => (string) ($part['part_type'] ?? ('part_' . ($idx + 1))),
                    'part_type' => $mappedType,
                    'part_length' => isset($part['length']) ? (int) $part['length'] : null,
                    'separator_after' => $part['separator'] ?? null,
                    'is_required' => $mappedType !== 'free_text',
                    'default_value' => $part['custom_value'] ?? (($part['part_type'] ?? null) === 'separator' ? ($part['separator'] ?? '-') : null),
                    'sequence_scope' => $part['sequence_scope'] ?? null,
                ];
            }, $payload['code_structure'], array_keys($payload['code_structure']));
        }

        return $payload;
    }

    private function transformConfiguration(DocumentTypeConfiguration $config): array
    {
        $parts = $config->structureParts->map(function ($part) {
            $reverseTypeMap = [
                'fixed_abbreviation' => 'type_code',
                'process_abbreviation' => 'process_code',
                'year' => 'year',
                'month' => 'month',
                'sequence' => 'sequence',
                'site_code' => 'site_code',
                'free_text' => ($part->separator_after ? 'separator' : 'custom'),
            ];
            $partType = $reverseTypeMap[$part->part_type] ?? 'custom';

            return [
                'id' => $part->id,
                'part_type' => $partType,
                'order' => $part->part_order,
                'length' => $part->part_length,
                'separator' => $part->separator_after,
                'custom_value' => $part->default_value,
                'sequence_scope' => $part->sequence_scope,
                'padding_char' => '0',
            ];
        })->values();

        return [
            'id' => $config->id,
            'type_code' => $config->abbreviation,
            'type_label' => $config->name,
            'description' => $config->description,
            'enterprise_id' => $config->enterprise_id,
            'site_id' => $config->site_id,
            'scope' => $config->scope,
            'is_active' => $config->is_active,
            'code_structure' => $parts,
            'structure_preview' => $config->generatePreviewCode(),
            'created_at' => $config->created_at,
            'updated_at' => $config->updated_at,
        ];
    }

    /**
     * Active ou désactive une configuration
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $config = DocumentTypeConfiguration::findOrFail($id);
        
        if (!$this->visibilityService->canEdit($request->user(), $config)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $config->update(['is_active' => !$config->is_active]);

        return response()->json([
            'message' => $config->is_active ? 'Configuration activée' : 'Configuration désactivée',
            'data' => ['is_active' => $config->is_active],
        ]);
    }

    /**
     * Partage une configuration avec des sites
     */
    public function shareWithSites(Request $request, int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        $validated = $request->validate([
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'required|integer|exists:sites,id',
            'conflict_strategy' => 'nullable|in:skip,override',
        ]);

        $config = DocumentTypeConfiguration::findOrFail($id);
        
        if (!$this->visibilityService->canEdit($request->user(), $config)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $results = $this->visibilityService->shareWithSites(
            $config,
            $validated['site_ids'],
            (string) ($validated['conflict_strategy'] ?? 'skip')
        );

        return response()->json([
            'message' => 'Partage effectué',
            'data' => $results
        ]);
    }

    /**
     * Partage une configuration avec des entreprises
     */
    public function shareWithEnterprises(Request $request, int $id): JsonResponse
    {
        return $this->rejectLegacyMutation();

        if (!$request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'enterprise_ids' => 'required|array|min:1',
            'enterprise_ids.*' => 'required|integer|exists:enterprises,id'
        ]);

        $config = DocumentTypeConfiguration::findOrFail($id);
        $results = $this->visibilityService->shareWithEnterprises($config, $validated['enterprise_ids']);

        return response()->json([
            'message' => 'Partage effectué',
            'data' => $results
        ]);
    }

    /**
     * Récupère les sites disponibles pour le partage
     */
    public function availableSitesForSharing(Request $request, int $id): JsonResponse
    {
        $config = DocumentTypeConfiguration::findOrFail($id);
        $sites = $this->visibilityService->getAvailableSitesForSharing($request->user(), $config);

        return response()->json(['data' => $sites]);
    }

    /**
     * Récupère les entreprises disponibles pour le partage
     */
    public function availableEnterprisesForSharing(Request $request, int $id): JsonResponse
    {
        $config = DocumentTypeConfiguration::findOrFail($id);
        $enterprises = $this->visibilityService->getAvailableEnterprisesForSharing($request->user(), $config);

        return response()->json(['data' => $enterprises]);
    }

    /**
     * Statistiques de visibilité
     */
    public function visibilityStats(Request $request): JsonResponse
    {
        $stats = $this->visibilityService->getVisibilityStats($request->user());
        return response()->json(['data' => $stats]);
    }

    /**
     * Filtres avancés
     */
    public function advancedFilters(Request $request): JsonResponse
    {
        $filters = $request->only([
            'site_id', 'enterprise_id', 'document_type_id', 
            'is_active', 'search', 'scope', 'created_from', 
            'created_to', 'min_documents', 'sort_by', 'sort_direction'
        ]);

        $configurations = $this->visibilityService->applyAdvancedFilters($request->user(), $filters);

        return response()->json([
            'data' => $configurations->values()
        ]);
    }

    private function notifyOtherSitesForPropagation(DocumentTypeConfiguration $config, User $actor): void
    {
        if ($config->scope !== 'site' || empty($config->site_id) || empty($config->enterprise_id)) {
            return;
        }

        $targetUsers = User::query()
            ->where('enterprise_id', (int) $config->enterprise_id)
            ->whereNotNull('site_id')
            ->where('site_id', '!=', (int) $config->site_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('collaborator_approval_status')
                    ->orWhereIn('collaborator_approval_status', ['approved', 'activation_sent']);
            })
            ->where(function ($q) {
                $q->where('user_type', 'super_admin')
                    ->orWhere('role', 'admin_entreprise')
                    ->orWhere('role', 'site_manager');
            })
            ->get();

        foreach ($targetUsers as $recipient) {
            $recipient->notify(new NomenclaturePropagationSuggestionNotification(
                $config,
                $actor,
                (int) $recipient->site_id
            ));
        }
    }

    private function rejectLegacyMutation(): JsonResponse
    {
        return response()->json([
            'message' => 'Les configurations legacy sont en lecture seule. Utilisez la modale Nomenclatures pour publier un template.',
        ], 409);
    }
}
