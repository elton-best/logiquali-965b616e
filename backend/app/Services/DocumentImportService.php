<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentTypeConfiguration;
use App\Models\CodeSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentImportService
{
    public function __construct(
        private CodeGenerationService $codeGenerationService
    ) {}

    /**
     * Analyse les documents existants et suggère des mappings
     */
    public function analyzeExistingDocuments(?int $siteId = null, ?int $enterpriseId = null): array
    {
        $query = Document::query()
            ->whereNull('document_type_configuration_id');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }
        if ($enterpriseId) {
            $query->where('enterprise_id', $enterpriseId);
        }

        $documents = $query->with(['documentType', 'process'])->get();

        $analysis = [
            'total_documents' => $documents->count(),
            'by_type' => [],
            'suggested_mappings' => [],
            'unmapped_documents' => []
        ];

        foreach ($documents as $document) {
            $typeKey = $document->document_type_id ?? 'unknown';
            
            if (!isset($analysis['by_type'][$typeKey])) {
                $analysis['by_type'][$typeKey] = [
                    'count' => 0,
                    'type_name' => $document->documentType->name ?? 'Unknown',
                    'sample_codes' => []
                ];
            }

            $analysis['by_type'][$typeKey]['count']++;
            
            if (count($analysis['by_type'][$typeKey]['sample_codes']) < 5) {
                $analysis['by_type'][$typeKey]['sample_codes'][] = $document->code;
            }
        }

        // Suggérer des configurations existantes
        $configurations = DocumentTypeConfiguration::active()
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->when($enterpriseId, fn($q) => $q->where('enterprise_id', $enterpriseId))
            ->get();

        foreach ($analysis['by_type'] as $typeId => $typeData) {
            $suggestedConfig = $configurations->first(function ($config) use ($typeId) {
                return $config->document_type_id == $typeId;
            });

            $analysis['suggested_mappings'][$typeId] = [
                'type_name' => $typeData['type_name'],
                'document_count' => $typeData['count'],
                'suggested_config_id' => $suggestedConfig?->id,
                'suggested_config_name' => $suggestedConfig?->type_label,
                'requires_manual_mapping' => !$suggestedConfig
            ];
        }

        return $analysis;
    }

    /**
     * Prévisualise l'import avec les mappings fournis
     */
    public function previewImport(array $mappings, ?int $siteId = null, ?int $enterpriseId = null): array
    {
        $preview = [
            'total_to_import' => 0,
            'by_configuration' => [],
            'warnings' => [],
            'errors' => []
        ];

        foreach ($mappings as $documentTypeId => $configId) {
            $config = DocumentTypeConfiguration::find($configId);
            
            if (!$config) {
                $preview['errors'][] = "Configuration {$configId} not found for type {$documentTypeId}";
                continue;
            }

            $query = Document::query()
                ->where('document_type_id', $documentTypeId)
                ->whereNull('document_type_configuration_id');

            if ($siteId) {
                $query->where('site_id', $siteId);
            }
            if ($enterpriseId) {
                $query->where('enterprise_id', $enterpriseId);
            }

            $count = $query->count();
            $preview['total_to_import'] += $count;

            $preview['by_configuration'][$configId] = [
                'config_name' => $config->type_label,
                'document_count' => $count,
                'will_preserve_codes' => true
            ];
        }

        return $preview;
    }

    /**
     * Exécute l'import des documents
     */
    public function executeImport(\App\Models\DocumentImport|array $importOrMappings, array $optionsOrValidationResults = []): array
    {
        // Support des deux signatures: nouvelle (mappings) et ancienne (DocumentImport)
        if ($importOrMappings instanceof \App\Models\DocumentImport) {
            return $this->executeImportFromModel($importOrMappings, $optionsOrValidationResults);
        }

        return $this->executeImportFromMappings($importOrMappings, $optionsOrValidationResults);
    }

    /**
     * Exécute l'import depuis un modèle DocumentImport (ancienne méthode)
     */
    private function executeImportFromModel(\App\Models\DocumentImport $import, array $validationResults): array
    {
        $imported = 0;
        $failed = 0;
        $rejected = 0;
        $errors = [];
        $correlationId = (string) ($validationResults['correlation_id'] ?? '');
        $startedAt = now();

        Log::info('Document import service execution started', [
            'correlation_id' => $correlationId,
            'import_id' => $import->id,
            'site_id' => $import->site_id,
            'user_id' => $import->user_id,
            'rows' => count((array) ($validationResults['rows'] ?? [])),
        ]);

        try {
            foreach ($validationResults['rows'] as $row) {
                if (!$row['is_valid']) {
                    $rejected++;
                    foreach ((array) ($row['errors'] ?? []) as $errorMessage) {
                        $errors[] = [
                            'row' => $row['row_number'],
                            'error' => $errorMessage,
                            'correction_attendue' => $this->suggestCorrection((string) $errorMessage),
                        ];
                    }
                    continue;
                }

                try {
                    DB::transaction(function () use ($import, $row, &$imported, &$failed, $validationResults): void {
                        $data = $row['data'];

                        // Résoudre le type de document et son abréviation
                        $docTypeCatalogId = isset($data['document_type_catalog_id'])
                            ? (int) $data['document_type_catalog_id'] : null;
                        $typeAbbreviation = null;
                        if ($docTypeCatalogId) {
                            $catalog = \App\Models\DocumentTypeCatalog::find($docTypeCatalogId);
                            $typeAbbreviation = $catalog?->abbreviation;
                        } elseif (!empty($data['type'])) {
                            $typeAbbreviation = strtoupper(trim((string) $data['type']));
                        }

                        // Résoudre le processus
                        $processId   = isset($data['process_id']) ? (int) $data['process_id'] : null;
                        $processCode = $processId
                            ? \App\Models\Process::find($processId)?->code
                            : null;

                        // Générer le code via la nomenclature active
                        $generatedCode               = null;
                        $documentTypeConfigurationId = null;
                        $nomenclatureTemplateId      = null;
                        $nomenclatureTemplateVersion = null;

                        if (!$typeAbbreviation) {
                            throw new \RuntimeException('Le type documentaire est requis pour générer le code via la nomenclature.');
                        }

                        $generation = app(\App\Services\DocumentCodeGenerationService::class)
                            ->generateForSiteAndType($import->site_id, $typeAbbreviation, $processId);

                        $generatedCode = $generation['code'];
                        $documentTypeConfigurationId = $generation['document_type_configuration_id'];
                        $nomenclatureTemplateId = $generation['nomenclature_template_id'];
                        $nomenclatureTemplateVersion = $generation['nomenclature_template_version'];
                        $finalCode = $generatedCode;

                        $statusMap = [
                            'brouillon' => 'draft',
                            'draft' => 'draft',
                            'en_revision' => 'pending_verification',
                            'pending_verification' => 'pending_verification',
                            'pending_approval' => 'pending_approval',
                            'valide' => 'approved',
                            'approved' => 'approved',
                            'obsolete' => 'obsolete',
                        ];
                        $status = $statusMap[strtolower((string) ($data['status'] ?? 'draft'))] ?? 'draft';

                        $metadata = [
                            'imported'                       => true,
                            'import_id'                      => $import->id,
                            'legacy_type'                    => $data['type'] ?? null,
                            'import_date'                    => $import->created_at->toISOString(),
                            'code_generated'                 => $generatedCode !== null,
                            'document_type_configuration_id' => $documentTypeConfigurationId,
                            'nomenclature_template_id'       => $nomenclatureTemplateId,
                        ];

                        $finalFilePath = $this->resolveImportedFilePath($data, $import->id);

                        $document = \App\Models\Document::create([
                            'site_id'                        => $import->site_id,
                            'enterprise_id'                  => \App\Models\Site::find($import->site_id)?->enterprise_id,
                            'process_id'                     => $processId,
                            'processus'                      => $processCode,
                            'document_type_configuration_id' => $documentTypeConfigurationId,
                            'nomenclature_template_id'       => $nomenclatureTemplateId,
                            'code'                           => $finalCode,
                            'title'                          => $data['title'] ?? $data['nom'] ?? 'Document importe',
                            'version'                        => $data['version'] ?? '1.0',
                            'description'                    => $data['description'] ?? null,
                            'file_path'                      => $finalFilePath,
                            'status'                         => $status,
                            'workflow_status'                => $status === 'approved' ? 'pending_approval' : 'pending_verification',
                            'needs_verification'             => true,
                            'author_id'                      => $import->user_id,
                            'code_status'                    => 'active',
                            'metadata'                       => $metadata,
                        ]);

                        // Auto-submit : soumet immédiatement le document au workflow
                        $autoSubmit = (bool) ($validationResults['execution_context']['auto_submit'] ?? false);
                        if ($autoSubmit && in_array($document->workflow_status, ['draft', 'pending_verification'], true)) {
                            $document->update([
                                'workflow_status' => 'pending_verification',
                                'needs_verification' => true,
                            ]);
                        }
                    });
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $row['row_number'],
                        'error' => $e->getMessage(),
                        'correction_attendue' => 'Vérifier les données de la ligne et relancer l’import.',
                    ];
                    Log::error('Import row failed', [
                        'correlation_id' => $correlationId,
                        'import_id' => $import->id,
                        'row' => $row['row_number'],
                        'error' => $e->getMessage(),
                        'data' => $row['data']
                    ]);
                    // Ne pas continuer si une erreur critique survient
                    if ($failed > 10) {
                        throw new \Exception('Too many import errors');
                    }
                }
            }

            $import->update([
                'status' => 'completed',
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'import_stats' => [
                    'correlation_id' => $correlationId,
                    'started_at' => $startedAt->toISOString(),
                    'completed_at' => now()->toISOString(),
                    'imported' => $imported,
                    'failed' => $failed,
                    'rejected' => $rejected,
                    'total_processed' => $imported + $failed + $rejected,
                ],
            ]);

            Log::info('Document import service execution completed', [
                'correlation_id' => $correlationId,
                'import_id' => $import->id,
                'imported' => $imported,
                'failed' => $failed,
                'rejected' => $rejected,
            ]);
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'import_stats' => [
                    'correlation_id' => $correlationId,
                    'started_at' => $startedAt->toISOString(),
                    'failed_at' => now()->toISOString(),
                    'imported' => $imported,
                    'failed' => $failed + 1,
                    'rejected' => $rejected,
                    'total_processed' => $imported + $failed + $rejected,
                ],
            ]);
            Log::error('Document import service execution aborted', [
                'correlation_id' => $correlationId,
                'import_id' => $import->id,
                'error' => $e->getMessage(),
                'imported' => $imported,
                'failed' => $failed,
                'rejected' => $rejected,
            ]);
            throw $e;
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'rejected' => $rejected,
            'errors' => $errors
        ];
    }

    /**
     * Exécute l'import depuis des mappings (nouvelle méthode)
     */
    private function executeImportFromMappings(array $mappings, array $options = []): array
    {
        $preserveCodes = $options['preserve_codes'] ?? true;
        $siteId = $options['site_id'] ?? null;
        $enterpriseId = $options['enterprise_id'] ?? null;

        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'imported_documents' => [],
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($mappings as $documentTypeId => $configId) {
                $config = DocumentTypeConfiguration::find($configId);
                
                if (!$config) {
                    $results['errors'][] = "Configuration {$configId} not found";
                    continue;
                }

                $query = Document::query()
                    ->where('document_type_id', $documentTypeId)
                    ->whereNull('document_type_configuration_id');

                if ($siteId) {
                    $query->where('site_id', $siteId);
                }
                if ($enterpriseId) {
                    $query->where('enterprise_id', $enterpriseId);
                }

                $documents = $query->get();

                foreach ($documents as $document) {
                    try {
                        $originalCode = $document->code;

                        if ($preserveCodes) {
                            // Conserver le code existant
                            $document->document_type_configuration_id = $config->id;
                            $document->code_status = 'active';
                            $document->save();

                            // Synchroniser la séquence si nécessaire
                            $this->syncSequenceFromCode($document, $config);
                        } else {
                            // Générer un nouveau code
                            $newCode = $this->codeGenerationService->generateCode($config->id, [
                                'site_id' => $document->site_id,
                                'process_id' => $document->process_id,
                                'document_type_id' => $document->document_type_id
                            ]);

                            $document->code = $newCode;
                            $document->document_type_configuration_id = $config->id;
                            $document->code_status = 'active';
                            $document->save();
                        }

                        $results['success_count']++;
                        $results['imported_documents'][] = [
                            'id' => $document->id,
                            'original_code' => $originalCode,
                            'new_code' => $document->code,
                            'config_id' => $config->id
                        ];

                    } catch (\Exception $e) {
                        $results['error_count']++;
                        $results['errors'][] = [
                            'document_id' => $document->id,
                            'code' => $document->code,
                            'error' => $e->getMessage()
                        ];
                        
                        Log::error('Document import error', [
                            'document_id' => $document->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Synchronise la séquence à partir d'un code existant
     */
    private function syncSequenceFromCode(Document $document, DocumentTypeConfiguration $config): void
    {
        $sequencePart = $config->structureParts()
            ->where('part_type', 'sequence')
            ->first();

        if (!$sequencePart) {
            return;
        }

        // Extraire le numéro de séquence du code existant
        $sequenceNumber = $this->extractSequenceFromCode($document->code, $config);
        
        if ($sequenceNumber === null) {
            return;
        }

        // Trouver ou créer la séquence appropriée
        $scope = $this->buildSequenceScope($document, $config, $sequencePart);
        
        $sequence = CodeSequence::firstOrCreate(
            array_merge(['document_type_configuration_id' => $config->id], $scope),
            ['current_number' => 0, 'available_numbers' => []]
        );

        // Mettre à jour current_number si nécessaire
        if ($sequenceNumber > $sequence->current_number) {
            $sequence->current_number = $sequenceNumber;
            $sequence->save();
        }
    }

    /**
     * Extrait le numéro de séquence d'un code
     */
    private function extractSequenceFromCode(string $code, DocumentTypeConfiguration $config): ?int
    {
        $parts = $config->structureParts()->orderBy('order')->get();
        $position = 0;

        foreach ($parts as $part) {
            if ($part->part_type === 'sequence') {
                $length = $part->length ?? 4;
                $sequenceStr = substr($code, $position, $length);
                return (int) $sequenceStr;
            }

            // Calculer la position approximative
            $position += $part->length ?? 2;
            if ($part->separator) {
                $position += strlen($part->separator);
            }
        }

        return null;
    }

    /**
     * Construit le scope de séquence pour un document
     */
    private function buildSequenceScope(Document $document, DocumentTypeConfiguration $config, \App\Models\CodeStructurePart $sequencePart): array
    {
        $scope = [];
        $sequenceScope = $sequencePart->sequence_scope ?? 'global';

        if (in_array($sequenceScope, ['by_type', 'by_type_process', 'by_type_year', 'by_type_process_year', 'by_type_process_year_month'])) {
            $scope['document_type_id'] = $document->document_type_id;
        }

        if (in_array($sequenceScope, ['by_type_process', 'by_type_process_year', 'by_type_process_year_month'])) {
            $scope['process_id'] = $document->process_id;
        }

        if (in_array($sequenceScope, ['by_type_year', 'by_type_process_year', 'by_type_process_year_month'])) {
            $scope['year'] = now()->year;
        }

        if ($sequenceScope === 'by_type_process_year_month') {
            $scope['month'] = now()->month;
        }

        return $scope;
    }

    /**
     * Parse un fichier Excel ou CSV
     */
    public function parseFile(string $filePath): array
    {
        // Utiliser Storage::path() pour supporter les disques fake en tests
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->parseCsvFile($fullPath);
        }

        if ($extension === 'json') {
            return $this->parseJsonFile($fullPath);
        }

        return $this->parseExcelFile($fullPath);
    }

    private function parseJsonFile(string $filePath): array
    {
        $data = json_decode(file_get_contents($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON import file is invalid: ' . json_last_error_msg());
        }

        if (!is_array($data) || empty($data)) {
            return [
                'headers' => [],
                'rows' => []
            ];
        }
        
        return [
            'headers' => array_keys($data[0] ?? []),
            'rows' => $data
        ];
    }

    private function resolveImportedFilePath(array $data, int $importId): ?string
    {
        $sourcePath = $data['__file_path__'] ?? null;
        if (!is_string($sourcePath) || $sourcePath === '' || !Storage::disk('local')->exists($sourcePath)) {
            return null;
        }

        $originalName = $data['__file_name__'] ?? basename($sourcePath);
        $originalName = is_string($originalName) && $originalName !== '' ? $originalName : basename($sourcePath);
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $safeName = $safeName !== '' ? $safeName : 'document';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? ".{$extension}" : '';

        $targetDir = "documents/imports/{$importId}";
        $targetPath = "{$targetDir}/{$safeName}-" . Str::uuid() . $extension;

        Storage::disk('local')->makeDirectory($targetDir);
        Storage::disk('local')->copy($sourcePath, $targetPath);

        return $targetPath;
    }

    private function parseExcelFile(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        return [
            'headers' => array_shift($data),
            'rows' => $data
        ];
    }

    private function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $headers = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        return [
            'headers' => $headers,
            'rows' => $rows
        ];
    }

    /**
     * Valide les données d'import selon le schéma de nomenclature actif.
     * Si aucun schéma n'est disponible, utilise la validation legacy.
     */
    public function validateImportData(\App\Models\DocumentImport $import, array $data): array
    {
        $validCount   = 0;
        $invalidCount = 0;
        $rows         = [];
        $correlationId = (string) (\Illuminate\Support\Str::uuid());

        // Charger le schéma de nomenclature pour ce site
        $schema = $this->buildNomenclatureSchema($import->site_id);
        $hasNomenclature = $schema['nomenclature'] !== null;

        // Construire la liste des colonnes requises selon le schéma
        $requiredKeys = [];
        if ($hasNomenclature) {
            foreach (array_merge($schema['columns_auto'], $schema['columns_dynamic'], $schema['columns_fixed']) as $col) {
                if (($col['required'] ?? false) && !($col['auto'] ?? false)) {
                    $requiredKeys[] = $col['key'];
                }
            }
        } else {
            $requiredKeys = ['code', 'title', 'type'];
        }

        foreach ($data['rows'] as $index => $row) {
            $rowData = is_array($row) && array_is_list($row)
                ? array_combine($data['headers'] ?? array_keys($row), $row)
                : $row;

            $errors   = [];
            $warnings = [];

            // SECURITY: Sanitisation CSV injection
            foreach ($rowData as $key => $value) {
                if (is_string($value) && strlen($value) > 0) {
                    $firstChar = $value[0];
                    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
                        $errors[] = "Injection CSV détectée dans le champ '{$key}' (commence par '{$firstChar}')";
                    }
                }
            }

            // Validation des colonnes requises
            foreach ($requiredKeys as $key) {
                if (empty($rowData[$key])) {
                    $colLabel = $this->getLabelForKey($key, $schema);
                    $errors[] = "Champ obligatoire manquant : {$colLabel}";
                }
            }

            // Validation du titre
            if (!empty($rowData['title']) && strlen($rowData['title']) > 255) {
                $errors[] = 'Le titre ne peut pas dépasser 255 caractères.';
            }

            // Validation du type de document
            $catalogId = !empty($rowData['document_type_catalog_id']) ? (int) $rowData['document_type_catalog_id'] : null;
            $processId = !empty($rowData['process_id']) ? (int) $rowData['process_id'] : null;
            $typeAbbreviation = null;

            if (!empty($rowData['document_type_catalog_id'])) {
                $catalog = \App\Models\DocumentTypeCatalog::query()
                    ->with('processes:id')
                    ->where('id', $catalogId)
                    ->where('is_active', true)
                    ->first();
                if (!$catalog) {
                    $errors[] = "Type de document invalide (ID: {$rowData['document_type_catalog_id']})";
                } else {
                    $typeAbbreviation = strtoupper((string) $catalog->abbreviation);
                    if ($processId && $catalog->processes->isNotEmpty()) {
                        $isAllowed = $catalog->processes->contains(fn ($p) => (int) $p->id === $processId);
                        if (!$isAllowed) {
                            $errors[] = "Le processus {$processId} n'est pas autorisé pour ce type documentaire.";
                        }
                    }
                }
            } elseif (!empty($rowData['type'])) {
                // Compatibilité legacy : accepter le champ 'type' textuel
                $resolvedType = $this->resolveDocumentType($rowData['type']);
                if (!$resolvedType && !in_array(strtolower((string) $rowData['type']), ['procedure', 'instruction', 'form', 'manual', 'record', 'policy'], true)) {
                    $errors[] = "Type de document invalide : {$rowData['type']}";
                } else {
                    $typeAbbreviation = strtoupper(substr((string) $rowData['type'], 0, 3));
                }
            }

            // Validation du processus
            if (!empty($rowData['process_id'])) {
                $processExists = \App\Models\Process::where('id', $rowData['process_id'])->exists();
                if (!$processExists) {
                    $errors[] = "Processus invalide (ID: {$rowData['process_id']})";
                }
            }

            $requiresProcess = $hasNomenclature
                || !empty($rowData['document_type_catalog_id'])
                || array_key_exists('process_id', $rowData);

            if ($requiresProcess && empty($rowData['process_id'])) {
                $errors[] = "Champ obligatoire manquant : Processus";
            }

            // Validation du code si fourni manuellement
            if (!empty($rowData['code'])) {
                if (\App\Models\Document::where('code', $rowData['code'])
                    ->where('site_id', $import->site_id)
                    ->exists()) {
                    $errors[] = "Code '{$rowData['code']}' déjà existant sur ce site.";
                }
            }

            if ($typeAbbreviation && $processId) {
                $expectedCode = $this->computeExpectedCode($import->site_id, $typeAbbreviation, $processId);
                if ($expectedCode && !empty($rowData['code']) && strtoupper((string) $rowData['code']) !== strtoupper((string) $expectedCode)) {
                    $errors[] = "Code incohérent avec la nomenclature active. Attendu: {$expectedCode}. Corrigez le document puis réimportez.";
                }
            }

            // Validation du statut
            if (!empty($rowData['status'])) {
                $validStatuses = ['draft', 'approved', 'obsolete', 'brouillon', 'valide', 'obsolète'];
                if (!in_array(strtolower($rowData['status']), $validStatuses)) {
                    $warnings[] = "Statut '{$rowData['status']}' non reconnu — 'draft' sera utilisé.";
                }
            }

            $isValid = empty($errors);
            if ($isValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $rows[] = [
                'row_number'   => $index + 2,
                'data'         => $rowData,
                'is_valid'     => $isValid,
                'errors'       => $errors,
                'error_details' => collect($errors)
                    ->map(fn (string $message) => [
                        'cause'               => $message,
                        'correction_attendue' => $this->suggestCorrection($message),
                    ])
                    ->values()
                    ->all(),
                'warnings' => $warnings,
            ];
        }

        $import->update([
            'status'             => 'validated',
            'validation_results' => [
                'correlation_id' => $correlationId,
                'valid_count'    => $validCount,
                'invalid_count'  => $invalidCount,
                'rows'           => $rows,
                'schema_used'    => $hasNomenclature ? 'nomenclature' : 'legacy',
            ],
            'import_stats' => [
                'correlation_id' => $correlationId,
                'validated_at'   => now()->toISOString(),
                'valid_count'    => $validCount,
                'invalid_count'  => $invalidCount,
                'total_rows'     => count((array) ($data['rows'] ?? [])),
            ],
        ]);

        Log::info('Document import validated', [
            'correlation_id' => $correlationId,
            'import_id'      => $import->id,
            'site_id'        => $import->site_id,
            'user_id'        => $import->user_id,
            'valid_count'    => $validCount,
            'invalid_count'  => $invalidCount,
            'schema_used'    => $hasNomenclature ? 'nomenclature' : 'legacy',
        ]);

        return [
            'correlation_id' => $correlationId,
            'valid_count'    => $validCount,
            'invalid_count'  => $invalidCount,
            'rows'           => $rows,
        ];
    }

    /**
     * Retourne le libellé lisible d'une clé de colonne.
     */
    private function getLabelForKey(string $key, array $schema): string
    {
        $allCols = array_merge(
            $schema['columns_auto']    ?? [],
            $schema['columns_dynamic'] ?? [],
            $schema['columns_fixed']   ?? [],
        );
        foreach ($allCols as $col) {
            if ($col['key'] === $key) {
                return $col['label'] ?? $key;
            }
        }
        return $key;
    }

    /**
     * Construit le schéma de colonnes attendu pour l'import
     * selon la nomenclature active du site.
     *
     * Retourne :
     * - columns_fixed   : colonnes toujours présentes (titre, version, description, statut)
     * - columns_dynamic : colonnes issues des parties free_text/custom de la nomenclature
     * - columns_auto    : colonnes auto-générées (code, type, processus, année, mois…)
     * - document_types  : liste des types disponibles pour ce site
     * - processes       : liste des processus disponibles pour ce site
     */
    public function buildNomenclatureSchema(?int $siteId, ?int $docTypeCatalogId = null): array
    {
        // Colonnes fixes toujours présentes
        $fixedColumns = [
            [
                'key'      => 'title',
                'label'    => 'Titre du document',
                'required' => true,
                'type'     => 'text',
                'max_length' => 255,
                'example'  => 'Procédure de gestion des risques',
            ],
            [
                'key'      => 'version',
                'label'    => 'Version',
                'required' => false,
                'type'     => 'text',
                'max_length' => 20,
                'example'  => '1.0',
                'default'  => '1.0',
            ],
            [
                'key'      => 'description',
                'label'    => 'Description',
                'required' => false,
                'type'     => 'text',
                'max_length' => 1000,
                'example'  => 'Description optionnelle du document',
            ],
            [
                'key'      => 'status',
                'label'    => 'Statut',
                'required' => false,
                'type'     => 'select',
                'options'  => ['draft', 'approved', 'obsolete'],
                'example'  => 'draft',
                'default'  => 'draft',
            ],
        ];

        // Colonnes auto-générées (informatives, pas à saisir manuellement)
        $autoColumns = [
            [
                'key'   => 'code',
                'label' => 'Code (généré automatiquement)',
                'auto'  => true,
                'note'  => 'Laissez vide — le code sera généré selon la nomenclature configurée',
            ],
        ];

        // Colonnes dynamiques issues de la nomenclature
        $dynamicColumns = [];
        $documentTypes  = [];
        $processes      = [];
        $nomenclatureInfo = null;

        if ($siteId) {
            // Récupérer les types de documents actifs pour ce site
            $site = \App\Models\Site::find($siteId);
            if ($site) {
                $docTypeCatalogs = \App\Models\DocumentTypeCatalog::where('is_active', true)
                    ->where(function ($q) use ($site) {
                        $q->where('site_id', $site->id)
                          ->orWhere('enterprise_id', $site->enterprise_id)
                          ->orWhereNull('site_id');
                    })
                    ->orderBy('display_order')
                    ->get();

                $documentTypes = $docTypeCatalogs->map(fn ($dt) => [
                    'id'           => $dt->id,
                    'name'         => $dt->name,
                    'abbreviation' => $dt->abbreviation,
                ])->values()->toArray();

                // Récupérer les processus du site
                $processes = \App\Models\Process::where('enterprise_id', $site->enterprise_id)
                    ->select('id', 'title', 'code')
                    ->orderBy('title')
                    ->get()
                    ->map(fn ($p) => [
                        'id'    => $p->id,
                        'title' => $p->title,
                        'code'  => $p->code,
                    ])->values()->toArray();

                // Récupérer le template de nomenclature actif
                $templateQuery = \App\Models\NomenclatureTemplate::where('site_id', $siteId)
                    ->where('is_active', true)
                    ->where('status', 'published');

                if ($docTypeCatalogId) {
                    $templateQuery->where('document_type_catalog_id', $docTypeCatalogId);
                }

                $template = $templateQuery->orderBy('version', 'desc')->first();

                if ($template) {
                    $nomenclatureInfo = [
                        'id'              => $template->id,
                        'name'            => $template->name,
                        'version'         => $template->version,
                        'separator'       => $template->separator ?? '-',
                        'preview_example' => $template->preview_example,
                    ];

                    // Extraire les parties qui nécessitent une saisie utilisateur
                    foreach ($template->format_structure ?? [] as $part) {
                        $partType = $part['type'] ?? '';

                        if (in_array($partType, ['free_text', 'custom']) && ($part['editable'] ?? true)) {
                            $dynamicColumns[] = [
                                'key'        => 'part_' . ($part['order'] ?? count($dynamicColumns) + 1),
                                'label'      => $part['label'] ?? 'Partie ' . ($part['order'] ?? ''),
                                'required'   => true,
                                'type'       => 'text',
                                'max_length' => $part['length'] ?? 50,
                                'part_order' => $part['order'] ?? null,
                                'part_type'  => $partType,
                                'example'    => $part['value'] ?? strtoupper(substr($part['label'] ?? 'VAL', 0, 3)),
                            ];
                        }

                        // Processus requis si la nomenclature contient process_code
                        if ($partType === 'process_code') {
                            $autoColumns[] = [
                                'key'      => 'process_id',
                                'label'    => 'ID Processus',
                                'required' => true,
                                'type'     => 'select',
                                'note'     => 'Identifiant du processus (voir liste ci-dessous)',
                                'example'  => $processes[0]['id'] ?? 1,
                            ];
                        }

                        // Type de document requis si la nomenclature contient document_type
                        if ($partType === 'document_type') {
                            $autoColumns[] = [
                                'key'      => 'document_type_catalog_id',
                                'label'    => 'ID Type de document',
                                'required' => true,
                                'type'     => 'select',
                                'note'     => 'Identifiant du type (voir liste ci-dessous)',
                                'example'  => $documentTypes[0]['id'] ?? 1,
                            ];
                        }
                    }

                    // Dédupliquer les colonnes auto
                    $autoColumns = collect($autoColumns)
                        ->unique('key')
                        ->values()
                        ->toArray();
                }
            }
        }

        // Si aucun template actif, colonnes minimales
        if (empty($nomenclatureInfo)) {
            $autoColumns[] = [
                'key'      => 'document_type_catalog_id',
                'label'    => 'ID Type de document',
                'required' => true,
                'type'     => 'select',
                'note'     => 'Identifiant du type (voir liste ci-dessous)',
                'example'  => $documentTypes[0]['id'] ?? 1,
            ];
            $autoColumns[] = [
                'key'      => 'process_id',
                'label'    => 'ID Processus',
                'required' => false,
                'type'     => 'select',
                'note'     => 'Identifiant du processus (voir liste ci-dessous)',
                'example'  => $processes[0]['id'] ?? 1,
            ];
        }

        return [
            'nomenclature'    => $nomenclatureInfo,
            'columns_fixed'   => $fixedColumns,
            'columns_auto'    => $autoColumns,
            'columns_dynamic' => $dynamicColumns,
            'all_columns'     => array_merge(
                array_column($autoColumns, 'key'),
                array_column($dynamicColumns, 'key'),
                array_column($fixedColumns, 'key'),
            ),
            'document_types'  => $documentTypes,
            'processes'       => $processes,
        ];
    }

    /**
     * Génère un template Excel avec les colonnes dynamiques selon la nomenclature.
     */
    public function generateTemplate(?int $siteId = null, ?int $docTypeCatalogId = null): string
    {
        $schema = $siteId
            ? $this->buildNomenclatureSchema($siteId, $docTypeCatalogId)
            : null;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ── Feuille 1 : Template d'import ──────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import');

        // Construire les en-têtes selon le schéma
        $headers = [];
        $examples = [];
        $notes    = [];

        if ($schema) {
            foreach (array_merge($schema['columns_auto'], $schema['columns_dynamic'], $schema['columns_fixed']) as $col) {
                if ($col['auto'] ?? false) continue; // Ignorer les colonnes purement auto (code)
                $headers[]  = $col['key'];
                $examples[] = $col['example'] ?? '';
                $notes[]    = ($col['required'] ? '* ' : '') . ($col['note'] ?? $col['label'] ?? $col['key']);
            }
        } else {
            // Fallback colonnes fixes
            $headers  = ['document_type_catalog_id', 'process_id', 'title', 'version', 'description', 'status'];
            $examples = [1, 1, 'Procédure de gestion des risques', '1.0', 'Description optionnelle', 'draft'];
            $notes    = ['* ID du type', '* ID du processus', '* Titre', 'Version (défaut: 1.0)', 'Description', 'draft|approved|obsolete'];
        }

        // Ligne 1 : notes / descriptions
        $sheet->fromArray([$notes], null, 'A1');
        // Ligne 2 : en-têtes (clés techniques)
        $sheet->fromArray([$headers], null, 'A2');
        // Ligne 3 : exemple
        $sheet->fromArray([$examples], null, 'A3');

        // Style en-têtes
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E3A8A']],
        ]);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']],
        ]);
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EFF6FF']],
        ]);

        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        // ── Feuille 2 : Référence types de documents ───────────────────
        if ($schema && !empty($schema['document_types'])) {
            $refSheet = $spreadsheet->createSheet();
            $refSheet->setTitle('Types de documents');
            $refSheet->fromArray([['ID', 'Nom', 'Abréviation']], null, 'A1');
            $refSheet->getStyle('A1:C1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DBEAFE']],
            ]);
            $row = 2;
            foreach ($schema['document_types'] as $dt) {
                $refSheet->fromArray([[$dt['id'], $dt['name'], $dt['abbreviation']]], null, "A{$row}");
                $row++;
            }
            foreach (['A', 'B', 'C'] as $col) {
                $refSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // ── Feuille 3 : Référence processus ───────────────────────────
        if ($schema && !empty($schema['processes'])) {
            $procSheet = $spreadsheet->createSheet();
            $procSheet->setTitle('Processus');
            $procSheet->fromArray([['ID', 'Titre', 'Code']], null, 'A1');
            $procSheet->getStyle('A1:C1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCFCE7']],
            ]);
            $row = 2;
            foreach ($schema['processes'] as $proc) {
                $procSheet->fromArray([[$proc['id'], $proc['title'], $proc['code'] ?? '']], null, "A{$row}");
                $row++;
            }
            foreach (['A', 'B', 'C'] as $col) {
                $procSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filePath = 'imports/template_' . time() . '.xlsx';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fullPath);

        return $filePath;
    }

    /**
     * Rollback d'un import (sélectif ou complet)
     * 
     * @param \App\Models\DocumentImport $import
     * @param array|null $documentIds Liste optionnelle d'IDs de documents à supprimer (null = tous)
     */
    public function rollbackImport(\App\Models\DocumentImport $import, ?array $documentIds = null): array
    {
        $deletedCount = 0;

        $query = Document::where('site_id', $import->site_id)
            ->whereJsonContains('metadata->imported', true)
            ->where(function ($query) use ($import) {
                $query->whereJsonContains('metadata->import_id', $import->id)
                    ->orWhereJsonContains('metadata->import_date', $import->created_at->toISOString());
            });

        // Rollback sélectif si des IDs sont fournis
        if ($documentIds !== null && !empty($documentIds)) {
            $query->whereIn('id', $documentIds);
        }

        $documents = $query->get();

        foreach ($documents as $document) {
            $document->delete();
            $deletedCount++;
        }

        // Marquer comme rolled_back seulement si rollback complet
        if ($documentIds === null || empty($documentIds)) {
            $import->update(['status' => 'rolled_back']);
        } else {
            // Rollback partiel : mettre à jour les stats
            $import->update([
                'imported_rows' => max(0, ($import->imported_rows ?? 0) - $deletedCount),
            ]);
        }

        return ['deleted_count' => $deletedCount];
    }

    /**
     * Résout un site par son nom si site_id n'est pas fourni
     */
    private function resolveSiteByName(string $siteName, int $enterpriseId): ?int
    {
        $site = \App\Models\Site::where('enterprise_id', $enterpriseId)
            ->where(function ($query) use ($siteName) {
                $query->where('name', 'LIKE', "%{$siteName}%")
                    ->orWhere('code', 'LIKE', "%{$siteName}%");
            })
            ->first();

        return $site?->id;
    }

    /**
     * Résout un type de document par son nom si le code n'est pas reconnu
     */
    private function resolveDocumentType(string $typeInput): ?string
    {
        $typeInput = strtolower(trim($typeInput));

        $typeMap = [
            'procédure' => 'procedure',
            'procedure' => 'procedure',
            'prc' => 'procedure',
            'instruction' => 'instruction',
            'prd' => 'instruction',
            'formulaire' => 'form',
            'form' => 'form',
            'for' => 'form',
            'manuel' => 'manual',
            'manual' => 'manual',
            'enregistrement' => 'record',
            'record' => 'record',
            'enr' => 'record',
            'politique' => 'policy',
            'policy' => 'policy',
            'pol' => 'policy',
        ];

        return $typeMap[$typeInput] ?? null;
    }

    private function computeExpectedCode(int $siteId, string $typeAbbreviation, int $processId): ?string
    {
        try {
            $generation = app(\App\Services\DocumentCodeGenerationService::class)
                ->generateForSiteAndType($siteId, $typeAbbreviation, $processId);

            return $generation['code'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function suggestCorrection(string $errorMessage): string
    {
        $normalized = mb_strtolower(trim($errorMessage));

        return match (true) {
            str_contains($normalized, 'code manquant') => 'Renseigner un code documentaire valide pour la ligne.',
            str_contains($normalized, 'code déjà existant') => 'Utiliser un code unique pour ce site ou supprimer le doublon.',
            str_contains($normalized, 'titre manquant') => 'Renseigner un titre de document non vide.',
            str_contains($normalized, 'type manquant') => 'Renseigner le type documentaire attendu.',
            str_contains($normalized, 'type invalide') => 'Utiliser un type valide: procedure, instruction, form, manual, record ou policy.',
            str_contains($normalized, 'injection csv') => 'Supprimer les caractères dangereux en début de cellule (=, +, -, @, tab, retour chariot).',
            default => 'Corriger les données de la ligne puis relancer la prévisualisation.',
        };
    }
}
