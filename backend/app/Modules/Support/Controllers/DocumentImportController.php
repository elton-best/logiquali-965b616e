<?php

namespace App\Modules\Support\Controllers;

use App\Models\Document;
use App\Models\DocumentTypeCatalog;
use App\Models\Permission;

use App\Http\Controllers\Controller;
use App\Models\DocumentImport;
use App\Notifications\DocumentImportCompletedNotification;
use App\Services\DocumentImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentImportController extends Controller
{
    public function __construct(
        private DocumentImportService $importService
    ) {}

    /**
     * Liste les imports de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentImport::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $imports = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $imports,
        ]);
    }

    /**
     * Détails d'un import
     */
    public function show(Request $request, int $importId): JsonResponse
    {
        $import = DocumentImport::findOrFail($importId);

        if ($import->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'valid_rows' => $import->valid_rows,
                'invalid_rows' => $import->invalid_rows,
                'imported_rows' => $import->imported_rows,
                'failed_rows' => $import->failed_rows,
                'validation_results' => $import->validation_results,
                'import_stats' => $import->import_stats,
                'error_message' => $import->error_message,
                'created_at' => $import->created_at,
                'completed_at' => $import->completed_at,
            ],
        ]);
    }

    /**
     * Upload d'un fichier Excel/CSV pour import
     */
    public function upload(Request $request): JsonResponse
    {
        $this->authorize('import_documents');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        // Compatibilité legacy: la permission import_documents reste suffisante.
        $siteId = (int) $request->input('site_id');
        $userSiteId = (int) $request->user()->site_id;
        $canImportOwnSite = $request->user()->can('import_documents.own_site');
        $canImportAllSites = $request->user()->can('import_documents.all_sites');

        if (($canImportOwnSite || $canImportAllSites) && !$canImportAllSites) {
            if (!$canImportOwnSite || $siteId !== $userSiteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée : vous ne pouvez importer que pour votre propre site',
                ], 403);
            }
        }

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        // SECURITY: Calculer le hash du fichier pour détecter les doublons
        $fileHash = hash_file('sha256', Storage::disk('local')->path($path));

        // Vérifier si ce fichier a déjà été importé récemment (7 derniers jours)
        $existingImport = DocumentImport::where('file_hash', $fileHash)
            ->where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'completed')
            ->first();

        if ($existingImport) {
            // Supprimer le fichier dupliqué
            Storage::disk('local')->delete($path);

            return response()->json([
                'success' => false,
                'message' => 'Ce fichier a déjà été importé le ' . $existingImport->created_at->format('d/m/Y à H:i') . '. Import dupliqué détecté.',
                'data' => [
                    'existing_import_id' => $existingImport->id,
                    'existing_import_date' => $existingImport->created_at->toISOString(),
                ],
            ], 409);
        }

        try {
            $parsed = $this->importService->parseFile($path);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);
            Log::error('Document import parse failed', [
                'user_id' => $request->user()->id,
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible de lire le fichier. Vérifiez qu\'il s\'agit d\'un fichier Excel ou CSV valide.',
            ], 422);
        }

        $import = DocumentImport::create([
            'user_id' => $request->user()->id,
            'site_id' => $request->input('site_id'),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_hash' => $fileHash,
            'status' => 'pending',
            'total_rows' => count($parsed['rows']),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $import->id,
                'filename' => $import->filename,
                'total_rows' => $import->total_rows,
                'headers' => $parsed['headers'],
            ],
        ]);
    }

    /**
     * Upload de plusieurs fichiers (PDF/Word) pour import en masse
     */
    public function uploadFiles(Request $request): JsonResponse
    {
        $this->authorize('import_documents');

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        $siteId = (int) $request->input('site_id');
        $userSiteId = (int) $request->user()->site_id;
        $canImportOwnSite = $request->user()->can('import_documents.own_site');
        $canImportAllSites = $request->user()->can('import_documents.all_sites');

        if (($canImportOwnSite || $canImportAllSites) && !$canImportAllSites) {
            if (!$canImportOwnSite || $siteId !== $userSiteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée : vous ne pouvez importer que pour votre propre site',
                ], 403);
            }
        }

        $files = $request->file('files');
        $importUuid = (string) Str::uuid();
        $baseDir = "imports/{$importUuid}";
        
        $rows = [];
        $fileHashes = [];
        foreach ($files as $file) {
            $path = $file->store("{$baseDir}/files", 'local');
            $filename = $file->getClientOriginalName();
            $title = preg_replace('/\.[^.]+$/', '', $filename); // Remove extension
            $fileHashes[] = hash_file('sha256', Storage::disk('local')->path($path));
            
            $rows[] = [
                'title' => $title,
                '__file_path__' => $path,
                '__file_name__' => $filename,
            ];
        }

        // Generate JSON data representing the "tabular" equivalent
        $jsonPath = "{$baseDir}/data.json";
        Storage::disk('local')->put($jsonPath, json_encode($rows, JSON_UNESCAPED_UNICODE));

        $import = DocumentImport::create([
            'user_id' => $request->user()->id,
            'site_id' => $request->input('site_id'),
            'filename' => count($files) . ' fichier(s) (PDF/Word)',
            'file_path' => $jsonPath,
            'file_hash' => hash('sha256', implode('|', $fileHashes)),
            'status' => 'pending',
            'total_rows' => count($rows),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $import->id,
                'filename' => $import->filename,
                'total_rows' => $import->total_rows,
                'headers' => ['title', '__file_path__', '__file_name__'],
            ],
        ]);
    }

    /**
     * Valide un import uploadé
     */
    public function validate(Request $request, int $importId): JsonResponse
    {
        $import = DocumentImport::findOrFail($importId);

        if ($import->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => 'required|integer|exists:processes,id',
        ]);

        $parsed = $this->importService->parseFile($import->file_path);

        // Injecter le contexte type/processus choisi dans la modal d'import
        // pour garantir la reconstruction du code et la validation cohérente.
        $parsed['rows'] = array_map(function ($row) use ($validated) {
            if (is_array($row) && array_is_list($row)) {
                return $row;
            }
            $data = is_array($row) ? $row : [];
            $data['document_type_catalog_id'] = $data['document_type_catalog_id'] ?? $validated['document_type_catalog_id'];
            $data['process_id'] = $data['process_id'] ?? $validated['process_id'];
            return $data;
        }, (array) ($parsed['rows'] ?? []));

        // SECURITY: Limite de 1000 lignes par import pour éviter les abus
        $rowCount = count($parsed['rows'] ?? []);

        if ($rowCount > 1000) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier contient ' . $rowCount . ' lignes. La limite est de 1000 lignes par import. Veuillez diviser votre fichier.',
            ], 422);
        }

        // SECURITY: Vérification permission bulk pour imports > 100 lignes
        if ($rowCount > 100 && !$request->user()->can('import_documents.bulk')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission refusée : les imports de plus de 100 lignes nécessitent la permission import_documents.bulk. Votre fichier contient ' . $rowCount . ' lignes.',
            ], 403);
        }

        $validationResults = $this->importService->validateImportData($import, $parsed);

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $import->id,
                'status' => $import->fresh()->status,
                'validation_results' => $validationResults,
            ],
        ]);
    }

    /**
     * Exécute un import validé
     */
    public function execute(Request $request, int $importId): JsonResponse
    {
        $import = DocumentImport::findOrFail($importId);

        if ($import->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if ($import->status !== 'validated') {
            return response()->json([
                'success' => false,
                'message' => 'L\'import doit être validé avant exécution.',
            ], 400);
        }

        $correlationId = (string) Str::uuid();

        // Compatibilité backward: accepter l'ancien contrat sans payload explicite.
        $validated = $request->validate([
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => 'required|integer|exists:processes,id',
        ]);

        $catalog = \App\Models\DocumentTypeCatalog::query()
            ->with('processes:id')
            ->find((int) $validated['document_type_catalog_id']);
        if ($catalog && $catalog->processes->isNotEmpty()) {
            $isAllowed = $catalog->processes->contains(fn ($p) => (int) $p->id === (int) $validated['process_id']);
            if (!$isAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le processus sélectionné n’est pas autorisé pour ce type documentaire.',
                ], 422);
            }
        }

        $executionContext = [
            'preview_confirmed' => (bool) $request->input('preview_confirmed', true),
            'conflict_strategy' => (string) $request->input('conflict_strategy', 'skip_invalid'),
            'code_generation_mode' => (string) $request->input('code_generation_mode', 'nomenclature_only'),
            'target_scope' => (string) $request->input('target_scope', 'site'),
            'document_type_catalog_id' => (int) $validated['document_type_catalog_id'],
            'process_id' => (int) $validated['process_id'],
            'auto_submit' => (bool) $request->input('auto_submit', false),
        ];

        validator($executionContext, [
            'preview_confirmed' => 'required|boolean|accepted',
            'conflict_strategy' => 'required|string|in:skip_invalid,stop_on_error',
            'code_generation_mode' => 'required|string|in:nomenclature_only',
            'target_scope' => 'required|string|in:site',
        ])->validate();

        $validationResults = (array) ($import->validation_results ?? []);
        $validationResults['execution_context'] = $executionContext;
        $validationResults['correlation_id'] = $correlationId;
        $import->update([
            'validation_results' => $validationResults,
        ]);

        Log::info('Document import execution started', [
            'correlation_id' => $correlationId,
            'import_id' => $import->id,
            'user_id' => $request->user()->id,
            'site_id' => $import->site_id,
            'context' => $executionContext,
        ]);

        try {
            $stats = $this->importService->executeImport($import, $validationResults);

            // Envoyer notification de fin d'import
            $request->user()->notify(new DocumentImportCompletedNotification($import->fresh(), $stats));

            return response()->json([
                'success' => true,
                'data' => [
                    'import_id' => $import->id,
                    'status' => $import->fresh()->status,
                    'stats' => [
                        'imported' => $stats['imported'] ?? 0,
                        'failed' => $stats['failed'] ?? 0,
                        'rejected' => $stats['rejected'] ?? 0,
                        'errors' => $stats['errors'] ?? [],
                        'correlation_id' => $correlationId,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Document import execution failed', [
                'correlation_id' => $correlationId,
                'import_id' => $import->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Rollback d'un import complété (sélectif avec limite 7 jours)
     */
    public function rollback(Request $request, int $importId): JsonResponse
    {
        // Compatibilité legacy: import_documents suffit pour le rollback.
        if (
            $request->user()->can('import_documents.rollback') === false
            && $request->user()->can('import_documents') === false
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Permission refusée : vous n\'avez pas le droit d\'annuler des imports',
            ], 403);
        }

        $import = DocumentImport::findOrFail($importId);

        if ($import->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if ($import->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Seul un import complété peut être annulé.',
            ], 400);
        }

        // SECURITY: Limite de 7 jours pour le rollback
        if ($import->created_at->lt(now()->subDays(7))) {
            return response()->json([
                'success' => false,
                'message' => 'Le rollback n\'est possible que dans les 7 jours suivant l\'import. Cet import date du ' . $import->created_at->format('d/m/Y') . '.',
            ], 400);
        }

        $validated = $request->validate([
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'integer|exists:documents,id',
        ]);

        $result = $this->importService->rollbackImport($import, $validated['document_ids'] ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $result['deleted_count'],
                'message' => 'Import annulé avec succès.',
            ],
        ]);
    }

    /**
     * Supprime un import
     */
    public function destroy(Request $request, int $importId): JsonResponse
    {
        $import = DocumentImport::findOrFail($importId);

        if ($import->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if ($import->file_path && Storage::exists($import->file_path)) {
            Storage::delete($import->file_path);
        }

        $import->delete();

        return response()->json(['success' => true, 'message' => 'Import supprimé.']);
    }

    /**
     * Retourne le schéma de colonnes attendu pour l'import,
     * basé sur la nomenclature active du site.
     *
     * GET /document-imports/nomenclature-schema?site_id=X&document_type_catalog_id=Y
     */
    public function nomenclatureSchema(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:sites,id',
            'document_type_catalog_id' => 'nullable|integer|exists:document_type_catalogs,id',
        ]);

        $siteId = (int) $request->input('site_id');
        $docTypeCatalogId = $request->input('document_type_catalog_id');

        $schema = $this->importService->buildNomenclatureSchema($siteId, $docTypeCatalogId);

        return response()->json([
            'success' => true,
            'data' => $schema,
        ]);
    }

    /**
     * Télécharge le template d'import (colonnes dynamiques selon nomenclature)
     *
     * GET /document-imports/template?site_id=X&document_type_catalog_id=Y
     */
    public function downloadTemplate(Request $request): JsonResponse
    {
        $siteId = $request->input('site_id')
            ? (int) $request->input('site_id')
            : $request->user()->site_id;

        $docTypeCatalogId = $request->input('document_type_catalog_id');

        $filePath = $this->importService->generateTemplate($siteId, $docTypeCatalogId);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => Storage::url($filePath),
                'filename' => 'template_import_documents.xlsx',
            ],
        ]);
    }

    /**
     * Analyse les documents existants (migration one-time)
     */
    public function analyze(Request $request): JsonResponse
    {
        $this->authorize('import_documents');

        $validated = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
            'enterprise_id' => 'nullable|integer|exists:enterprises,id',
        ]);

        $analysis = $this->importService->analyzeExistingDocuments(
            $validated['site_id'] ?? null,
            $validated['enterprise_id'] ?? null
        );

        return response()->json($analysis);
    }

    /**
     * Prévisualise l'import (migration one-time)
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('import_documents');

        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'required|integer|exists:document_type_configurations,id',
            'site_id' => 'nullable|integer|exists:sites,id',
            'enterprise_id' => 'nullable|integer|exists:enterprises,id',
        ]);

        $preview = $this->importService->previewImport(
            $validated['mappings'],
            $validated['site_id'] ?? null,
            $validated['enterprise_id'] ?? null
        );

        return response()->json(array_merge($preview, [
            'execution_requirements' => [
                'preview_confirmed' => true,
                'conflict_strategy' => ['skip_invalid', 'stop_on_error'],
                'code_generation_mode' => ['nomenclature_only'],
                'target_scope' => ['site'],
            ],
        ]));
    }

    /**
     * Exécute la migration one-time des documents existants vers les configurations.
     */
    public function executeMigration(Request $request): JsonResponse
    {
        $this->authorize('import_documents');

        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'required|integer|exists:document_type_configurations,id',
            'preserve_codes' => 'nullable|boolean',
            'site_id' => 'nullable|integer|exists:sites,id',
            'enterprise_id' => 'nullable|integer|exists:enterprises,id',
            'preview_confirmed' => 'required|boolean|accepted',
            'conflict_strategy' => 'required|string|in:skip_invalid,stop_on_error',
            'code_generation_mode' => 'required|string|in:nomenclature_only',
            'target_scope' => 'required|string|in:site',
        ]);

        $results = $this->importService->executeImport($validated['mappings'], [
            'preserve_codes' => $validated['preserve_codes'] ?? true,
            'site_id' => $validated['site_id'] ?? null,
            'enterprise_id' => $validated['enterprise_id'] ?? null,
            'execution_context' => [
                'preview_confirmed' => (bool) $validated['preview_confirmed'],
                'conflict_strategy' => $validated['conflict_strategy'],
                'code_generation_mode' => $validated['code_generation_mode'],
                'target_scope' => $validated['target_scope'],
            ],
        ]);

        return response()->json($results);
    }
}
