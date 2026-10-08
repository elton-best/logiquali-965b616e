<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesGeneratedDocumentContext;
use App\Http\Resources\ApplicationScopeResource;
use App\Http\Resources\DocumentResource;
use App\Models\ApplicationScope;
use App\Models\Process;
use App\Models\Site;
use App\Services\ProcessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ApplicationScopeController extends Controller
{
    use ResolvesGeneratedDocumentContext;

    public function index(Request $request)
    {
        $query = ApplicationScope::with(['site']);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('is_current')) {
            $query->where('is_current', filter_var($request->is_current, FILTER_VALIDATE_BOOLEAN));
        }

        $scopes = $query->orderByDesc('id')->paginate(20);
        return ApplicationScopeResource::collection($scopes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'version' => 'nullable|string|max:50',
            'is_current' => 'nullable|boolean',
            'objective' => 'nullable|string',
            'scope' => 'nullable|string',
            'document_objective' => 'nullable|string',
            'scope_definition' => 'nullable|string',
            'referenced_documents' => 'nullable|array',
            'processes' => 'nullable|array',
            'included_processes' => 'nullable|array',
            'products_services' => 'nullable|array',
            'organizational_units' => 'nullable|array',
            'locations' => 'nullable|array',
            'scope_exclusions' => 'nullable|string',
            'exclusions' => 'nullable|string',
            'exclusions_justification' => 'nullable|string',
            'iso_exclusions' => 'nullable|string',
            'iso_exclusions_justification' => 'nullable|string',
            'norm_exclusions' => 'nullable|array',
            'norm_exclusions_justifications' => 'nullable|array',
            'applicable_norms' => 'nullable|array',
            'document_generated' => 'nullable|boolean',
            'document_path' => 'nullable|string|max:500',
            'generated_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if (empty($validated['enterprise_id']) && !empty($validated['site_id'])) {
            $validated['enterprise_id'] = Site::where('id', $validated['site_id'])->value('enterprise_id');
        }

        $validated = $this->normalizeNormExclusionsStorage($validated);
        $scope = ApplicationScope::create($validated);
        
        $this->syncProcessesToGlobal($scope);
        
        return new ApplicationScopeResource($scope->load(['site']));
    }

    public function show(ApplicationScope $applicationScope)
    {
        return new ApplicationScopeResource($applicationScope->load(['site']));
    }

    public function update(Request $request, ApplicationScope $applicationScope)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'version' => 'nullable|string|max:50',
            'is_current' => 'nullable|boolean',
            'objective' => 'nullable|string',
            'scope' => 'nullable|string',
            'document_objective' => 'nullable|string',
            'scope_definition' => 'nullable|string',
            'referenced_documents' => 'nullable|array',
            'processes' => 'nullable|array',
            'included_processes' => 'nullable|array',
            'products_services' => 'nullable|array',
            'organizational_units' => 'nullable|array',
            'locations' => 'nullable|array',
            'scope_exclusions' => 'nullable|string',
            'exclusions' => 'nullable|string',
            'exclusions_justification' => 'nullable|string',
            'iso_exclusions' => 'nullable|string',
            'iso_exclusions_justification' => 'nullable|string',
            'norm_exclusions' => 'nullable|array',
            'norm_exclusions_justifications' => 'nullable|array',
            'applicable_norms' => 'nullable|array',
            'document_generated' => 'nullable|boolean',
            'document_path' => 'nullable|string|max:500',
            'generated_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if (empty($validated['enterprise_id']) && !empty($validated['site_id'])) {
            $validated['enterprise_id'] = Site::where('id', $validated['site_id'])->value('enterprise_id');
        }
        $validated = $this->normalizeNormExclusionsStorage($validated, $applicationScope);
        $applicationScope->update($validated);
        
        $this->syncProcessesToGlobal($applicationScope);
        
        return new ApplicationScopeResource($applicationScope->load(['site']));
    }

    /**
     * Export Application Scope as PDF
     */
    public function exportDocx(Request $request, ApplicationScope $applicationScope)
    {
        try {
            $generationContext = $this->resolveGeneratedDocumentContext($request, (int) $applicationScope->site_id);

            // Etape 1: Réserver le code et l'ID du document en base de données
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $applicationScope->site_id,
                'process_id' => $generationContext['process_id'],
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'application_scope',
                'source_type' => 'application_scope',
                'source_id' => $applicationScope->id,
                'source_updated_at' => $applicationScope->updated_at?->toISOString(),
                'title' => 'Domaine d’application - ' . ($applicationScope->site->name ?? 'Site'),
                'description' => 'Document du domaine d’application généré automatiquement.',
                'created_by' => $request->user()?->id,
                'type' => $generationContext['type'],
                'force_new' => true,
                'metadata' => $generationContext['metadata'],
            ]);

            // Etape 2: Générer le fichier PDF avec le branding et le code calculé
            $enterprise = $applicationScope->site?->enterprise;
            if (!$enterprise) {
                return response()->json(['message' => 'Entreprise introuvable'], 404);
            }

            $htmlContent = view('pdf.application-scope', [
                'scope' => $applicationScope,
                'document' => $document,
            ])->render();

            $pdfContent = app(\App\Services\PdfGeneratorService::class)->generateDocument(
                $enterprise,
                'application_scope',
                [
                    'document_id' => $document->id,
                    'title' => $document->title,
                    'code' => $document->code,
                    'version' => $document->version ?? '1.0',
                    'html' => $htmlContent,
                ],
                ['is_draft' => ($document->workflow_status !== 'approved')]
            );

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }
            $filePath = $tempDir . DIRECTORY_SEPARATOR . 'Domaine_Application_' . $applicationScope->id . '_' . time() . '.pdf';
            file_put_contents($filePath, $pdfContent);

            // Etape 3: Mettre à jour le document avec le fichier physique
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'document_id' => $document->id,
                'site_id' => (int) $applicationScope->site_id,
                'process_id' => $generationContext['process_id'],
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'application_scope',
                'source_type' => 'application_scope',
                'source_id' => $applicationScope->id,
                'source_updated_at' => $applicationScope->updated_at?->toISOString(),
                'title' => $document->title,
                'description' => 'Document du domaine d’application généré automatiquement.',
                'file_source_path' => $filePath,
                'created_by' => $request->user()?->id,
                'type' => $generationContext['type'],
                'force_new' => true,
                'store_file' => true,
                'metadata' => $generationContext['metadata'],
            ]);
            
            $filename = 'Domaine_Application_' . ($applicationScope->site->name ?? 'Site') . '_' . now()->format('Y-m-d') . '.pdf';
            
            return response()
                ->download($filePath, $filename)
                ->header('X-Generated-Document-Id', (string) $document->id)
                ->deleteFileAfterSend(true);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException|\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Application Scope draft document as PDF (no download)
     */
    public function generateDraftDocx(Request $request, ApplicationScope $applicationScope)
    {
        $generationContext = $this->resolveGeneratedDocumentContext($request, (int) $applicationScope->site_id);

        // Etape 1: Réserver le code et l'ID du document en base de données
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => (int) $applicationScope->site_id,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'application_scope',
            'source_type' => 'application_scope',
            'source_id' => $applicationScope->id,
            'source_updated_at' => $applicationScope->updated_at?->toISOString(),
            'title' => 'Domaine d’application - ' . ($applicationScope->site->name ?? 'Site'),
            'description' => 'Document du domaine d’application généré automatiquement.',
            'created_by' => $request->user()?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        // Etape 2: Générer le fichier PDF avec le branding et le code calculé
        $enterprise = $applicationScope->site?->enterprise;
        if (!$enterprise) {
            return response()->json(['message' => 'Entreprise introuvable'], 404);
        }

        $htmlContent = view('pdf.application-scope', [
            'scope' => $applicationScope,
            'document' => $document,
        ])->render();

        $pdfContent = app(\App\Services\PdfGeneratorService::class)->generateDocument(
            $enterprise,
            'application_scope',
            [
                'document_id' => $document->id,
                'title' => $document->title,
                'code' => $document->code,
                'version' => $document->version ?? '1.0',
                'html' => $htmlContent,
            ],
            ['is_draft' => true]
        );

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $filePath = $tempDir . DIRECTORY_SEPARATOR . 'Domaine_Application_' . $applicationScope->id . '_' . time() . '.pdf';
        file_put_contents($filePath, $pdfContent);

        // Etape 3: Mettre à jour le document avec le fichier physique
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'document_id' => $document->id,
            'site_id' => (int) $applicationScope->site_id,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'application_scope',
            'source_type' => 'application_scope',
            'source_id' => $applicationScope->id,
            'source_updated_at' => $applicationScope->updated_at?->toISOString(),
            'title' => $document->title,
            'description' => 'Document du domaine d’application généré automatiquement.',
            'file_source_path' => $filePath,
            'created_by' => $request->user()?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }

    private function normalizeNormExclusionsStorage(array $validated, ?ApplicationScope $scope = null): array
    {
        $hasNormExclusionsColumn = Schema::hasColumn('application_scopes', 'norm_exclusions');
        $hasNormExclusionsJustificationsColumn = Schema::hasColumn('application_scopes', 'norm_exclusions_justifications');

        if ($hasNormExclusionsColumn && $hasNormExclusionsJustificationsColumn) {
            return $validated;
        }

        $metadata = $validated['metadata'] ?? ($scope?->metadata ?? []) ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }

        if (array_key_exists('norm_exclusions', $validated)) {
            $metadata['norm_exclusions'] = $validated['norm_exclusions'];
            unset($validated['norm_exclusions']);
        }

        if (array_key_exists('norm_exclusions_justifications', $validated)) {
            $metadata['norm_exclusions_justifications'] = $validated['norm_exclusions_justifications'];
            unset($validated['norm_exclusions_justifications']);
        }

        $validated['metadata'] = $metadata;

        return $validated;
    }

    private function syncProcessesToGlobal(ApplicationScope $scope): void
    {
        $processes = $scope->processes ?? [];
        if (!is_array($processes) || empty($processes)) return;

        $processService = app(ProcessService::class);
        $userId = request()->user()?->id;
        if (!$userId) return;

        $existingByName = Process::query()
            ->where('site_id', $scope->site_id)
            ->get()
            ->mapWithKeys(function (Process $process) {
                $key = $this->normalizeProcessName((string) $process->title);
                return $key !== '' ? [$key => $process] : [];
            });

        $syncedProcesses = [];

        foreach ($processes as $proc) {
            $proc = is_array($proc) ? $proc : [];
            $processName = isset($proc['name']) ? trim((string) $proc['name']) : '';
            if ($processName === '') continue;

            // Define mapping from scope type to process category
            $categoryMap = [
                'Support' => 'support',
                'support' => 'support',
                'Réalisation' => 'operationnel',
                'realization' => 'operationnel',
                'realisation' => 'operationnel',
                'operationnel' => 'operationnel',
                'Management' => 'pilotage',
                'management' => 'pilotage',
                'Pilotage' => 'pilotage',
                'pilotage' => 'pilotage',
                'Direction' => 'pilotage',
                'direction' => 'pilotage',
            ];
            $category = $categoryMap[$proc['type'] ?? ''] ?? 'operationnel';

            $existingProcess = $existingByName->get($this->normalizeProcessName($processName));
            $syncedProcess = $existingProcess;

            $computedAbbreviation = $proc['abbreviation'] ?? null;
            if (empty($computedAbbreviation)) {
                $cleanTitle = preg_replace('/[^A-Za-z0-9\s]/', '', \Illuminate\Support\Str::ascii($processName));
                $words = array_values(array_filter(explode(' ', strtoupper($cleanTitle))));

                if (count($words) >= 3) {
                    $computedAbbreviation = substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
                } elseif (count($words) === 2) {
                    $computedAbbreviation = substr($words[0], 0, 2) . substr($words[1], 0, 1);
                } else {
                    $computedAbbreviation = substr(str_replace(' ', '', strtoupper($cleanTitle)), 0, 3);
                }
            }
            if (empty($computedAbbreviation)) {
                $computedAbbreviation = 'OPE';
            }

            if (!$existingProcess) {
                try {
                    $syncedProcess = $processService->createProcess([
                        'enterprise_id' => $scope->enterprise_id ?: Site::where('id', $scope->site_id)->value('enterprise_id'),
                        'site_id' => $scope->site_id,
                        'title' => $processName,
                        'abbreviation' => strtoupper($computedAbbreviation),
                        'category' => $category,
                        'pilot_id' => $userId,
                        'status' => 'draft',
                        'purpose' => 'Processus créé depuis le domaine d\'application',
                    ], $userId);
                } catch (\Exception $e) {
                    Log::error('Failed to sync process from scope', [
                        'process_name' => $processName,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // Update existing process basic info
                $dirty = false;
                if ($existingProcess->category !== $category) {
                    $existingProcess->category = $category;
                    $dirty = true;
                }
                
                $newAbbrev = strtoupper($computedAbbreviation);
                if ($newAbbrev !== null && $existingProcess->abbreviation !== $newAbbrev) {
                    $existingProcess->abbreviation = $newAbbrev;
                    $dirty = true;
                }

                if ($dirty) {
                    try {
                        $existingProcess->save();
                    } catch (\Exception $e) {
                        Log::error('Failed to update synced process from scope', [
                            'process_id' => $existingProcess->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            if ($syncedProcess) {
                $syncedProcesses[] = [
                    ...$proc,
                    'id' => (int) $syncedProcess->id,
                    'code' => $syncedProcess->code,
                    'abbreviation' => $syncedProcess->abbreviation ?? ($proc['abbreviation'] ?? null),
                ];
            } else {
                $syncedProcesses[] = $proc;
            }
        }

        if (!empty($syncedProcesses)) {
            $scope->forceFill(['processes' => $syncedProcesses])->saveQuietly();
        }
    }

    private function normalizeProcessName(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim(mb_strtolower($value)));
        return is_string($normalized) ? $normalized : '';
    }
}
