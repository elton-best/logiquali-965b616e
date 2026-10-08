<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentTypeConfiguration;
use App\Models\SecurityAuditLog;
use App\Models\Site;
use App\Models\User;
use App\Models\DocumentVersion;
use App\Models\DocumentCodeRelease;
use App\Notifications\Document\DocumentWorkflowNotification;
use App\Services\DocumentWorkflowAuditTrailService;
use App\Services\ProcedureDocxGenerator;
use App\Services\DocumentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\Process;
use App\Models\Modification;

class DocumentController extends Controller
{
    public function categories()
    {
        $categories = DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('order')
            ->get();

        return response()->json($categories);
    }

    public function index(Request $request)
    {
        $query = Document::query()->with(['site', 'process', 'author', 'approver', 'verifier', 'typeConfiguration', 'currentVersion']);
        $this->scopeDocumentQuery($query, $request->user());

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }
        if ($request->filled('type')) {
            // Filtrer par abréviation de type → résoudre via DocumentTypeConfiguration
            $typeAbbr = $request->string('type');
            $query->whereHas('typeConfiguration', function ($q) use ($typeAbbr) {
                $q->where('abbreviation', strtoupper($typeAbbr));
            });
        }
        if ($request->filled('document_type_configuration_id')) {
            $query->where('document_type_configuration_id', $request->integer('document_type_configuration_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            $query->where(function ($q) {
                $q->where('status', '!=', 'obsolete')
                  ->orWhereNull('status');
            });
        }
        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->string('workflow_status'));
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->string('etat'));
        }
        if ($request->filled('processus')) {
            $query->where('processus', $request->string('processus'));
        }
        if ($request->filled('process_id')) {
            $query->where('process_id', $request->integer('process_id'));
        }
        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($inner) use ($q) {
                $inner->where('code', 'like', "%{$q}%")
                      ->orWhere('title', 'like', "%{$q}%");
            });
        }

        $this->applyGeneratedDraftVisibilityScope($query, $request->user());

        $documents = $query->latest('id')->get();
        $user = $request->user();

        if ($user && $user->user_type !== 'super_admin') {
            $documents = $documents
                ->reject(fn (Document $document) => $this->isHiddenDraftModification($document, $user))
                ->values();
        }

        return response()->json($documents);
    }

    public function store(Request $request)
    {
        // ── Normalisation des champs legacy → modernes ────────────────────────
        // Accepte les deux formats (legacy: nom/processus/etat et moderne: title/status)
        // pour assurer la compatibilité pendant la transition.
        $this->normalizeLegacyFields($request);

        $validated = $request->validate([
            'site_id'                        => 'nullable|exists:sites,id',
            'category_id'                    => 'nullable|exists:document_categories,id',
            'process_id'                     => 'nullable|exists:processes,id',
            'processus'                      => 'nullable|string|max:100',
            'title'                          => 'required|string|max:255',
            'code'                           => 'nullable|string|max:255',
            'document_type_configuration_id' => 'nullable|integer|exists:document_type_configurations,id',
            'version'                        => 'nullable|string|max:20',
            'type'                           => 'nullable|string|max:20',
            'status'                         => 'nullable|in:draft,pending_approval,approved,obsolete',
            'etat'                           => 'nullable|in:a_etablir,en_cours,termine',
            'author_id'                      => 'nullable|exists:users,id',
            'description'                    => 'nullable|string',
            'file'                           => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'fichier'                        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'effective_date'                 => 'nullable|date',
            'review_due_date'                => 'nullable|date',
            'date_creation'                  => 'nullable|date',
            'periodicite_revision'           => 'nullable|integer|min:1',
            'retention_period_years'         => 'nullable|integer|min:0|max:100',
            'is_confidential'                => 'nullable|boolean',
            'confidentiality_level'          => 'nullable|in:public,internal,confidential,restricted',
            'keywords'                       => 'nullable|string|max:2000',
            'tags'                           => 'nullable|array',
            'tags.*'                         => 'string|max:100',
            'language'                       => 'nullable|string|max:5',
            'metadata'                       => 'nullable|array',
            'module_type'                    => 'nullable|string|max:120',
            'module_id'                      => 'nullable|integer|min:1',
            'source_type'                    => 'nullable|string|max:120',
            'source_module'                  => 'nullable|string|max:150',
            'source_submodule'               => 'nullable|string|max:150',
            'source_section'                 => 'nullable|string|max:150',
            'needs_verification'             => 'nullable|boolean',
        ]);

        $siteId = (int) ($validated['site_id'] ?? $request->header('X-Site-ID') ?? 0);
        if ($siteId <= 0) {
            return response()->json(['message' => 'Le site est requis.'], 422);
        }

        $site = Site::query()->findOrFail($siteId);
        $this->assertSiteAccess($site, $request->user());

        $userId   = $request->user()?->id;
        $authorId = $validated['author_id'] ?? $userId;
        if (!$authorId) {
            return response()->json(['message' => 'Auteur introuvable.'], 422);
        }

        // ── Validation du processus ───────────────────────────────────────────
        if (!empty($validated['process_id'])) {
            $processExists = Process::query()
                ->where('id', (int) $validated['process_id'])
                ->where(function ($q) use ($siteId, $site) {
                    $q->where('site_id', $siteId)
                      ->orWhere('enterprise_id', $site->enterprise_id);
                })
                ->exists();

            if (!$processExists) {
                return response()->json([
                    'message' => 'Le processus sélectionné est invalide pour ce site.',
                    'errors'  => ['process_id' => ['Le processus sélectionné est invalide pour ce site.']],
                ], 422);
            }
        }

        // ── Résolution du type documentaire ──────────────────────────────────
        $typeAbbreviation = null;
        $documentTypeConfigurationId = $validated['document_type_configuration_id'] ?? null;

        if ($documentTypeConfigurationId) {
            $config = DocumentTypeConfiguration::query()->findOrFail((int) $documentTypeConfigurationId);
            $typeAbbreviation = $config->abbreviation;
        } elseif (!empty($validated['type'])) {
            $rawType = strtoupper(trim((string) $validated['type']));
            // Accepter les abréviations directes (PRC, FOR…) ou les types modernes (procedure, form…)
            $typeAbbreviation = in_array($rawType, ['POL', 'PRC', 'PRD', 'FOR', 'ENR', 'MAN'], true)
                ? $rawType
                : $this->mapModernTypeToNomenclatureAbbreviation(strtolower($rawType));
        }

        if (!$typeAbbreviation) {
            throw ValidationException::withMessages([
                'type' => ['Le type documentaire doit correspondre à une configuration active pour générer un code.'],
            ]);
        }

        // ── Génération du code via nomenclature ───────────────────────────────
        $generation = $this->generateUnifiedDocumentCode(
            $siteId,
            $typeAbbreviation,
            isset($validated['process_id']) ? (int) $validated['process_id'] : null
        );

        $documentCode                = (string) $generation['code'];
        $documentTypeConfigurationId = $generation['document_type_configuration_id'];
        $nomenclatureTemplateId      = $generation['nomenclature_template_id'];
        $nomenclatureTemplateVersion = $generation['nomenclature_template_version'];

        // ── Upload fichier ────────────────────────────────────────────────────
        $storedFilePath = null;
        if ($request->hasFile('fichier')) {
            $storedFilePath = $request->file('fichier')->store('documents', 'public');
        } elseif ($request->hasFile('file')) {
            $storedFilePath = $request->file('file')->store('documents', 'public');
        }

        // ── Calcul prochaine révision ─────────────────────────────────────────
        $periodicite = isset($validated['periodicite_revision']) ? (int) $validated['periodicite_revision'] : null;
        $prochaineRevision = ($periodicite && $periodicite > 0) ? now()->addMonths($periodicite) : null;

        // ── Métadonnées ───────────────────────────────────────────────────────
        $metadata = $this->mergeSourceContext(
            is_array($validated['metadata'] ?? null) ? $validated['metadata'] : [],
            $validated
        );
        $metadata['code_generated']                 = true;
        $metadata['document_type_configuration_id'] = $documentTypeConfigurationId;
        if ($nomenclatureTemplateId) {
            $metadata['nomenclature_template_id']      = $nomenclatureTemplateId;
            $metadata['nomenclature_template_version'] = $nomenclatureTemplateVersion;
        }
        if (!empty($generation['metadata'])) {
            $metadata = array_merge($metadata, $generation['metadata']);
        }

        // ── Création du document ──────────────────────────────────────────────
        // ── Résolution module/submodule/section ───────────────────────────────
        $sourceType = $validated['source_type'] ?? 'inventory';
        $sourceMap  = \App\Support\DocumentSourceTypeMap::resolve($sourceType);
        $sourceModule    = $validated['source_module']    ?? $sourceMap['module'];
        $sourceSubmodule = $validated['source_submodule'] ?? $sourceMap['submodule'];
        $sourceSection   = $validated['source_section']   ?? $sourceMap['section'];

        $document = Document::create([
            'site_id'                        => $siteId,
            'enterprise_id'                  => $site->enterprise_id,
            'category_id'                    => $validated['category_id'] ?? null,
            'process_id'                     => $validated['process_id'] ?? null,
            'processus'                      => $validated['processus'] ?? null,
            'document_type_configuration_id' => $documentTypeConfigurationId,
            'nomenclature_template_id'       => $nomenclatureTemplateId,
            'nomenclature_template_version'  => $nomenclatureTemplateVersion,
            'title'                          => $validated['title'],
            'code'                           => $documentCode,
            'code_status'                    => 'active',
            'version'                        => $validated['version'] ?? '1.0',
            'status'                         => 'draft',
            'workflow_status'                => 'draft',
            'etat'                           => $validated['etat'] ?? 'a_etablir',
            'author_id'                      => $authorId,
            'description'                    => $validated['description'] ?? null,
            'file_path'                      => $storedFilePath,
            'collaboration_version'          => 1,
            'is_active'                      => true,
            'effective_date'                 => $validated['effective_date'] ?? null,
            'review_due_date'                => $validated['review_due_date'] ?? null,
            'periodicite_revision'           => $periodicite,
            'prochaine_revision'             => $prochaineRevision,
            'retention_period_years'         => $validated['retention_period_years'] ?? null,
            'is_confidential'                => (bool) ($validated['is_confidential'] ?? false),
            'confidentiality_level'          => $validated['confidentiality_level'] ?? 'internal',
            'keywords'                       => $validated['keywords'] ?? null,
            'tags'                           => $validated['tags'] ?? null,
            'language'                       => $validated['language'] ?? 'fr',
            'module_type'                    => $validated['module_type'] ?? null,
            'module_id'                      => $validated['module_id'] ?? null,
            'source_type'                    => $sourceType,
            'source_module'                  => $sourceModule,
            'source_submodule'               => $sourceSubmodule,
            'source_section'                 => $sourceSection,
            'needs_verification'             => (bool) ($validated['needs_verification'] ?? true),
            'metadata'                       => $metadata,
        ]);

        $this->markReusedCodeIfAny($document);

        // ── Version initiale ──────────────────────────────────────────────────
        if ($storedFilePath) {
            $file = $request->hasFile('fichier') ? $request->file('fichier') : $request->file('file');
            DocumentVersion::query()->create([
                'document_id'        => $document->id,
                'version_number'     => $document->version ?? '1.0',
                'file_path'          => $storedFilePath,
                'file_size'          => $file?->getSize(),
                'file_mime_type'     => $file?->getMimeType(),
                'file_original_name' => $file?->getClientOriginalName(),
                'change_summary'     => 'Version initiale',
                'created_by'         => $authorId,
                'is_current'         => true,
            ]);
        }

        // ── Déclenchement automatique du workflow ─────────────────────────────
        // Le document est créé en draft. On le soumet immédiatement au workflow
        // (vérification si needs_verification=true, sinon directement approbation).
        $this->submitToWorkflow($document, $request->user());

        $document->refresh();

        return response()->json(
            $document->load(['site', 'process', 'author', 'typeConfiguration', 'versions']),
            201
        );
    }

    public function generateProcedure(Request $request, ProcedureDocxGenerator $generator)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'processus' => 'required|string',
            'type' => 'required|in:PRC,PRD',
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'etat' => 'required|in:a_etablir,en_cours,termine',
            'statut' => 'nullable|in:brouillon,en_revision,valide',
            'validated_at' => 'nullable|date',
            'date_creation' => 'required|date',
            'periodicite_revision' => 'nullable|integer',
            'procedure_data' => 'required',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'metadata' => 'nullable|array',
            'source_type' => 'nullable|string|max:120',
            'source_module' => 'nullable|string|max:150',
            'source_submodule' => 'nullable|string|max:150',
            'source_section' => 'nullable|string|max:150',
        ]);

        $procedureData = $validated['procedure_data'];
        if (is_string($procedureData)) {
            $decoded = json_decode($procedureData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'procedure_data' => ['Le contenu de la procédure est invalide.'],
                ]);
            }
            $procedureData = $decoded;
        }

        if (!is_array($procedureData)) {
            throw ValidationException::withMessages([
                'procedure_data' => ['Le contenu de la procédure est invalide.'],
            ]);
        }

        $userId = $request->user()?->id;
        $validated['created_by'] = $userId;
        $validated['version'] = '1.0';
        $validated['statut'] = $validated['statut'] ?? 'brouillon';

        $site = Site::find($validated['site_id']);
        $enterpriseId = $site?->enterprise_id;

        if (!empty(trim((string) ($validated['code'] ?? '')))) {
            throw ValidationException::withMessages([
                'code' => ['Le code est généré automatiquement par la nomenclature active du site.'],
            ]);
        }

        if (!empty($validated['process_id'])) {
            $processExists = Process::query()
                ->where('id', (int) $validated['process_id'])
                ->where('site_id', (int) $validated['site_id'])
                ->exists();

            if (!$processExists) {
                return response()->json([
                    'message' => 'Le processus sélectionné est invalide pour ce site.',
                    'errors' => [
                        'process_id' => ['Le processus sélectionné est invalide pour ce site.'],
                    ],
                ], 422);
            }
        }

        if (empty($validated['code'])) {
            $generation = $this->generateUnifiedDocumentCode(
                (int) $validated['site_id'],
                (string) $validated['type'],
                isset($validated['process_id']) ? (int) $validated['process_id'] : null
            );
            $validated['code'] = (string) $generation['code'];
            $validated['document_type_configuration_id'] = $generation['document_type_configuration_id'];
            $meta = is_array($validated['metadata'] ?? null) ? $validated['metadata'] : [];
            $meta['code_generated'] = true;
            $meta['document_type_configuration_id'] = $generation['document_type_configuration_id'];
            $meta['nomenclature_template_id'] = $generation['nomenclature_template_id'];
            $meta['nomenclature_template_version'] = $generation['nomenclature_template_version'];
            $validated['metadata'] = $meta;
        }
        $validated['metadata'] = $this->mergeSourceContext(
            is_array($validated['metadata'] ?? null) ? $validated['metadata'] : [],
            $validated
        );

        if (array_key_exists('periodicite_revision', $validated)) {
            $periodicite = (int) $validated['periodicite_revision'];
            $validated['periodicite_revision'] = $periodicite > 0 ? $periodicite : null;
            $validated['prochaine_revision'] = $periodicite > 0
                ? now()->addMonths($periodicite)
                : null;
        }

        $docPayload = array_merge($procedureData, [
            'title' => $validated['nom'],
            'code' => $validated['code'] ?? null,
            'version' => $validated['version'] ?? null,
            'date_creation' => $validated['date_creation'],
            'redacteur' => $procedureData['redacteur'] ?? null,
            'verificateur' => $procedureData['verificateur'] ?? null,
            'approbateur' => $procedureData['approbateur'] ?? null,
        ]);

        $generated = $generator->generate($docPayload);
        $storedPath = 'documents/' . $generated['filename'];
        Storage::disk('public')->put($storedPath, file_get_contents($generated['path']));
        @unlink($generated['path']);

        $metadata = array_merge(
            is_array($procedureData) ? $procedureData : [],
            is_array($validated['metadata'] ?? null) ? $validated['metadata'] : []
        );
        $metadata['generated_from'] = 'procedure_docx';

        $site = Site::query()->find($validated['site_id']);
        $sourceMap = \App\Support\DocumentSourceTypeMap::resolve($validated['source_type'] ?? 'procedure');

        $periodicite = isset($validated['periodicite_revision']) ? (int) $validated['periodicite_revision'] : null;

        $document = Document::query()->create([
            'site_id'            => $validated['site_id'],
            'enterprise_id'      => $site?->enterprise_id,
            'process_id'         => $validated['process_id'] ?? null,
            'processus'          => $validated['processus'],
            'title'              => $validated['nom'],
            'code'               => $validated['code'],
            'code_status'        => 'active',
            'document_type_configuration_id' => $validated['document_type_configuration_id'] ?? null,
            'version'            => $validated['version'],
            'status'             => 'draft',
            'workflow_status'    => 'draft',
            'etat'               => $validated['etat'],
            'source_type'        => $validated['source_type'] ?? 'procedure',
            'source_module'      => $sourceMap['module'],
            'source_submodule'   => $sourceMap['submodule'],
            'source_section'     => $sourceMap['section'],
            'file_path'          => $storedPath,
            'author_id'          => $userId,
            'description'        => $validated['description'] ?? null,
            'needs_verification' => true,
            'is_active'          => true,
            'periodicite_revision' => $periodicite ?: null,
            'prochaine_revision'   => $periodicite ? now()->addMonths($periodicite) : null,
            'metadata'           => $metadata,
        ]);

        DocumentVersion::query()->create([
            'document_id'        => $document->id,
            'version_number'     => $validated['version'],
            'file_path'          => $storedPath,
            'change_summary'     => 'Procédure générée automatiquement',
            'created_by'         => $userId,
            'is_current'         => true,
        ]);

        // Annexes — créées comme documents indépendants liés via metadata
        foreach ($request->file('attachments', []) as $index => $file) {
            if (!$file) continue;

            $attachmentPath = $file->store('documents', 'public');
            $annexeGeneration = $this->generateUnifiedDocumentCode((int) $validated['site_id'], 'ENR', $validated['process_id'] ?? null);

            Document::query()->create([
                'site_id'         => $validated['site_id'],
                'enterprise_id'   => $site?->enterprise_id,
                'process_id'      => $validated['process_id'] ?? null,
                'processus'       => $validated['processus'],
                'title'           => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'code'            => $annexeGeneration['code'],
                'code_status'     => 'active',
                'version'         => '1.0',
                'status'          => 'draft',
                'workflow_status' => 'draft',
                'etat'            => $validated['etat'],
                'source_type'     => 'procedure_annex',
                'source_module'   => $sourceMap['module'],
                'source_submodule' => $sourceMap['submodule'],
                'source_section'  => $sourceMap['section'],
                'file_path'       => $attachmentPath,
                'author_id'       => $userId,
                'description'     => 'Annexe liée à ' . $document->code,
                'needs_verification' => true,
                'is_active'       => true,
                'metadata'        => ['parent_document_id' => $document->id, 'type_relation' => 'complete'],
            ]);
        }

        $this->submitToWorkflow($document, $request->user());

        return (new DocumentResource($document->fresh()->load(['site', 'process', 'author', 'typeConfiguration'])))->response()->setStatusCode(201);
    }

    private function isLegacyDocumentInventoryRequest(Request $request): bool
    {
        // SUPPRIMÉ — plus de branche legacy. Toutes les requêtes passent par store() unifié.
        return false;
    }

    private function isModernDocumentRequest(Request $request): bool
    {
        // SUPPRIMÉ — plus de détection de format. index() utilise toujours Document.
        return true;
    }

    private function mapModernTypeToNomenclatureAbbreviation(string $type): ?string
    {
        $normalized = strtolower(trim($type));
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'policy' => 'POL',
            'procedure' => 'PRC',
            'instruction' => 'PRD',
            'form' => 'FOR',
            'record', 'manual', 'other' => 'ENR',
            default => null,
        };
    }

    private function generateUnifiedDocumentCode(int $siteId, string $typeAbbreviation, ?int $processId = null): array
    {
        $generation = app(\App\Services\DocumentCodeGenerationService::class)
            ->generateForSiteAndType($siteId, $typeAbbreviation, $processId);

        return [
            ...$generation,
            'metadata' => [],
        ];
    }

    private function consumeAvailableReleasedCode(Site $site, DocumentTypeConfiguration $config, string $abbreviation): ?DocumentCodeRelease
    {
        return DB::transaction(function () use ($site, $config, $abbreviation) {
            $release = DocumentCodeRelease::query()
                ->where('entreprise_id', (int) $site->enterprise_id)
                ->whereNull('reused_at')
                ->where(function ($query) use ($site, $config, $abbreviation) {
                    $query->whereHas('originalDocument', function ($docQuery) use ($site, $config) {
                        $docQuery->where('site_id', (int) $site->id)
                            ->where('document_type_configuration_id', (int) $config->id);
                    })->orWhere(function ($fallbackQuery) use ($abbreviation) {
                        $fallbackQuery->whereNull('original_document_id')
                            ->where('code', 'like', $abbreviation . '-%');
                    });
                })
                ->orderBy('released_at') // FIFO
                ->lockForUpdate()
                ->first();

            return $release;
        });
    }

    private function markReusedCodeIfAny(Document $document): void
    {
        $metadata = is_array($document->metadata) ? $document->metadata : [];
        $releaseId = (int) ($metadata['reused_release_id'] ?? 0);
        if ($releaseId <= 0) {
            return;
        }

        DocumentCodeRelease::query()
            ->whereKey($releaseId)
            ->whereNull('reused_at')
            ->update([
                'reused_by_document_id' => $document->id,
                'reused_at' => now(),
            ]);
    }

    public function show(Request $request, string $document)
    {
        $modernDocument = Document::query()
            ->with(['site', 'process', 'author', 'approver', 'verifier', 'typeConfiguration', 'category', 'workflow', 'currentVersion', 'versions', 'workflowEvents.actor'])
            ->findOrFail($document);
        $this->assertSiteAccess($modernDocument->site, $request->user());
        $this->assertGeneratedDraftAccess($modernDocument, $request->user());

        return (new DocumentResource($modernDocument))->response();
    }

    public function update(Request $request, string $document)
    {
        $modernDocument = Document::query()->findOrFail($document);
        $this->assertModificationDocumentAccess($modernDocument, $request->user());
            $isApproved = ((string) ($modernDocument->workflow_status ?? '') === 'approved')
                || ((string) ($modernDocument->status ?? '') === 'approved');
            $validated = $request->validate([
                'category_id' => 'nullable|exists:document_categories,id',
                'process_id' => 'nullable|exists:processes,id',
                'title' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:255',
                'version' => 'nullable|string|max:20',
                'type' => 'nullable|in:procedure,instruction,form,record,manual,policy,other',
                'status' => 'nullable|in:draft,pending_approval,approved,obsolete',
                'description' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
                'effective_date' => 'nullable|date',
                'review_due_date' => 'nullable|date',
                'retention_period_years' => 'nullable|integer|min:0|max:100',
                'is_confidential' => 'nullable|boolean',
                'confidentiality_level' => 'nullable|in:public,internal,confidential,restricted',
                'keywords' => 'nullable|string|max:2000',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:100',
                'language' => 'nullable|string|max:5',
                'metadata' => 'nullable|array',
                'module_type' => 'nullable|string|max:120',
                'module_id' => 'nullable|integer|min:1',
                'source_type' => 'nullable|string|max:120',
                'source_module' => 'nullable|string|max:150',
                'source_submodule' => 'nullable|string|max:150',
                'source_section' => 'nullable|string|max:150',
            ]);

            if (array_key_exists('code', $validated)) {
                throw ValidationException::withMessages([
                    'code' => [$isApproved
                        ? 'Le code est définitif après validation et ne peut plus être modifié.'
                        : 'Le code est généré automatiquement par la nomenclature active du site.'
                    ],
                ]);
            }

            $existingVersion = trim((string) ($modernDocument->version ?? '1.0'));
            $newVersion = trim((string) ($validated['version'] ?? $existingVersion));
            $hasNewFile = $request->hasFile('file');

            if ($hasNewFile) {
                if (!empty($modernDocument->file_path)) {
                    Storage::disk('public')->delete($modernDocument->file_path);
                }
                $storedPath = $request->file('file')->store('documents', 'public');
                $validated['file_path'] = $storedPath;

                DocumentVersion::query()
                    ->where('document_id', $modernDocument->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                DocumentVersion::query()->create([
                    'document_id' => $modernDocument->id,
                    'version_number' => $newVersion,
                    'file_path' => $storedPath,
                    'file_size' => $request->file('file')?->getSize(),
                    'file_mime_type' => $request->file('file')?->getMimeType(),
                    'file_original_name' => $request->file('file')?->getClientOriginalName(),
                    'change_summary' => 'Mise à jour du document',
                    'created_by' => $request->user()?->id ?? $modernDocument->author_id,
                    'is_current' => true,
                ]);
            }

            if (array_key_exists('version', $validated) || $hasNewFile) {
                $validated['version'] = $newVersion;
            }

            $hasSourceContextUpdate = array_key_exists('source_module', $validated)
                || array_key_exists('source_submodule', $validated)
                || array_key_exists('source_section', $validated);
            if ($hasSourceContextUpdate || array_key_exists('metadata', $validated)) {
                $baseMetadata = array_key_exists('metadata', $validated) && is_array($validated['metadata'])
                    ? $validated['metadata']
                    : (is_array($modernDocument->metadata) ? $modernDocument->metadata : []);
                $validated['metadata'] = $this->mergeSourceContext($baseMetadata, $validated);
            }

            $modernDocument->update($validated);
            $modernDocument->load(['site', 'process', 'author', 'approver', 'verifier', 'category', 'workflow', 'currentVersion', 'versions', 'workflowEvents.actor']);

            return (new DocumentResource($modernDocument))->response();
    }

    public function destroy(Request $request, string $document)
    {
        $modernDocument = Document::query()->with('versions')->findOrFail($document);
        $isApproved = ((string) ($modernDocument->workflow_status ?? '') === 'approved')
            || ((string) ($modernDocument->status ?? '') === 'approved');
        if ($isApproved) {
            return response()->json([
                'message' => 'Suppression interdite: le document est validé et son code est définitif.',
            ], 422);
        }
        if ($modernDocument->file_path) {
            Storage::disk('public')->delete($modernDocument->file_path);
        }
        foreach ($modernDocument->versions as $version) {
            if ($version->file_path) {
                Storage::disk('public')->delete($version->file_path);
            }
        }
        $modernDocument->delete();

        return response()->json(['message' => 'Document supprimé']);
    }


    public function uploadVersion(Request $request, Document $document)
    {
        $this->assertModificationDocumentAccess($document, $request->user());
        $isApproved = ((string) ($document->workflow_status ?? '') === 'approved')
            || ((string) ($document->status ?? '') === 'approved');
        $baseApprovedVersion = $isApproved ? (string) ($document->version ?: '1.0') : null;

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'change_summary' => 'nullable|string|max:2000',
            'version' => 'nullable|string|max:20',
        ]);

        $nextVersion = $isApproved
            ? $baseApprovedVersion
            : trim((string) ($validated['version'] ?? $document->version ?? '1.0'));
        $storedPath = $request->file('file')->store('documents/versions', 'public');

        DocumentVersion::query()
            ->where('document_id', $document->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'file_size' => $request->file('file')?->getSize(),
            'file_mime_type' => $request->file('file')?->getMimeType(),
            'file_original_name' => $request->file('file')?->getClientOriginalName(),
            'change_summary' => $validated['change_summary'] ?? null,
            'created_by' => $request->user()?->id ?? $document->author_id,
            'is_current' => true,
        ]);

        $documentUpdates = [
            'version' => $nextVersion,
            'file_path' => $storedPath,
            'collaboration_version' => (int) ($document->collaboration_version ?? 0) + 1,
        ];

        if ($isApproved) {
            $metadata = (array) ($document->metadata ?? []);
            $metadata['versioning'] = array_merge((array) ($metadata['versioning'] ?? []), [
                'base_approved_version' => $baseApprovedVersion,
                'draft_started_by' => $request->user()?->id,
                'draft_started_at' => now()->toISOString(),
            ]);

            $documentUpdates = array_merge($documentUpdates, [
                'status' => 'draft',
                'workflow_status' => 'draft',
                'approved_at' => null,
                'verifier_id' => null,
                'approver_id' => null,
                'verified_at' => null,
                'verified_by' => null,
                'metadata' => $metadata,
            ]);
        }

        $document->update($documentUpdates);

        return response()->json(['data' => $version->load('creator')], 201);
    }

    public function download(Request $request, Document $document, ?int $versionId = null)
    {
        $this->assertSiteAccess($document->site, $request->user());
        $this->assertGeneratedDraftAccess($document, $request->user());
        $version = null;
        if ($versionId) {
            $version = $document->versions()->where('id', $versionId)->firstOrFail();
        } else {
            $version = $document->currentVersion()->first();
        }

        $path = $version?->file_path ?: $document->file_path;
        if (!$path) {
            return response()->json(['message' => 'Aucun fichier disponible pour ce document'], 404);
        }

        // Fallback : essayer disk public puis disk local
        $disk = null;
        if (Storage::disk('public')->exists($path)) {
            $disk = Storage::disk('public');
        } elseif (Storage::disk('local')->exists($path)) {
            $disk = Storage::disk('local');
        }

        if (!$disk) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $downloadName = $version?->file_original_name
            ? basename($version->file_original_name)
            : null;
        if (!$downloadName) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $downloadName = sprintf('%s-v%s.%s', $document->code ?: 'document', $document->version ?: '1.0', $extension ?: 'dat');
        }
        // Remplacer les caractères invalides dans le nom de fichier
        $downloadName = str_replace(['/', '\\'], '_', $downloadName);

        // Journaliser le téléchargement
        \App\Models\DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $request->user()?->id ?? 1,
            action: 'downloaded',
            fromStatus: $document->workflow_status,
            toStatus: $document->workflow_status,
            comment: 'Téléchargement du document.'
        );

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $absolutePath = $disk->path($path);
        return response()->download($absolutePath, $downloadName);
    }

    public function preview(Request $request, Document $document)
    {
        $this->assertSiteAccess($document->site, $request->user());
        $this->assertGeneratedDraftAccess($document, $request->user());

        $versionId = $request->integer('version_id');
        $version = $versionId
            ? $document->versions()->where('id', $versionId)->firstOrFail()
            : $document->currentVersion()->first();

        $path = $version?->file_path ?: $document->file_path;
        if (!$path) {
            return response()->json(['message' => 'Aucun fichier disponible pour ce document'], 404);
        }

        // Fallback : essayer disk public puis disk local
        $disk = null;
        if (Storage::disk('public')->exists($path)) {
            $disk = Storage::disk('public');
        } elseif (Storage::disk('local')->exists($path)) {
            $disk = Storage::disk('local');
        }

        if (!$disk) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $downloadName = $version?->file_original_name
            ? basename($version->file_original_name)
            : null;
        if (!$downloadName) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $downloadName = sprintf('%s-v%s.%s', $document->code ?: 'document', $document->version ?: '1.0', $extension ?: 'dat');
        }
        $downloadName = str_replace(['/', '\\'], '_', $downloadName);

        // Journaliser la prévisualisation
        \App\Models\DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $request->user()?->id ?? 1,
            action: 'previewed',
            fromStatus: $document->workflow_status,
            toStatus: $document->workflow_status,
            comment: 'Prévisualisation du document.'
        );

        // Pour les documents obsolètes : régénérer à la volée avec filigrane EXPIRÉ
        // si le fichier stocké est un PDF (les DOCX/XLSX sont servis tels quels)
        if ($document->status === 'obsolete' && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
            try {
                $finalizer = app(\App\Services\DocumentApprovalFinalizer::class);
                $pdfContent = $finalizer->generateObsoletePreview($document);
                if ($pdfContent) {
                    return response($pdfContent, 200, [
                        'Content-Type'        => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
                    ]);
                }
            } catch (\Throwable) {
                // Fallback : servir le fichier stocké
            }
        }

        return response()->file($disk->path($path), [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    public function submitForApproval(Document $document)
    {
        $this->assertSiteAccess($document->site, request()->user());

        $fromState = (string) ($document->workflow_status ?: 'draft');
        $targetState = (bool) $document->needs_verification ? 'pending_verification' : 'pending_approval';
        $allowed = [
            'draft' => ['pending_verification', 'pending_approval'],
            'rejected' => ['pending_verification', 'pending_approval'],
        ];

        if (!in_array($targetState, $allowed[$fromState] ?? [], true)) {
            return response()->json([
                'message' => "Transition invalide: {$fromState} -> {$targetState}",
            ], 400);
        }

        $document->update([
            'status' => $targetState === 'pending_approval' ? 'pending_approval' : 'draft',
            'workflow_status' => $targetState,
            'rejection_reason' => null,
            'verified_at' => $targetState === 'pending_verification' ? null : $document->verified_at,
            'verified_by' => $targetState === 'pending_verification' ? null : $document->verified_by,
            'verifier_id' => $targetState === 'pending_verification' ? null : $document->verifier_id,
        ]);

        if ($document->approver_id) {
            DB::table('document_approvals')->insert([
                'document_id' => $document->id,
                'document_version_id' => $document->currentVersion?->id,
                'user_id' => $document->approver_id,
                'step_order' => 1,
                'role' => 'approver',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $actor = request()->user();
        if ($targetState === 'pending_verification') {
            $verifiers = $this->findWorkflowUsersByPermission('verify_documents', $document, $actor?->id);
            foreach ($verifiers as $verifier) {
                $verifier->notify(new DocumentWorkflowNotification('verification_request', $document, $actor));
            }
        } else {
            $approvers = $this->findWorkflowUsersByPermission('approve_documents', $document, $actor?->id);
            foreach ($approvers as $approver) {
                $approver->notify(new DocumentWorkflowNotification('approval_request', $document, $actor));
            }
        }

        SecurityAuditLog::logEvent(
            eventType: 'document_workflow_transition',
            action: 'submit_for_approval',
            resourceType: 'document',
            resourceId: $document->id,
            metadata: [
                'document_id' => $document->id,
                'site_id' => $document->site_id,
                'from_state' => $fromState,
                'to_state' => $targetState,
            ],
            riskLevel: 'medium',
        );

        app(DocumentWorkflowAuditTrailService::class)->recordEvent(
            document: $document->fresh(),
            eventType: 'submit_for_approval',
            fromStatus: $fromState,
            toStatus: $targetState,
            actorUserId: $actor?->id,
            metadata: [
                'site_id' => $document->site_id,
            ],
        );

        return (new DocumentResource($document->fresh()->load(['site', 'process', 'author', 'approver', 'verifier', 'category', 'workflowEvents.actor'])))->response();
    }

    public function workflowIntegrity(Request $request, Document $document)
    {
        $this->assertSiteAccess($document->site, $request->user());

        $result = app(DocumentWorkflowAuditTrailService::class)->verifyDocumentChain($document);

        return response()->json([
            'data' => [
                'document_id' => $document->id,
                ...$result,
            ],
        ]);
    }

    public function previewCode(Request $request)
    {
        $validated = $request->validate([
            'site_id'                        => 'nullable|integer|exists:sites,id',
            'type'                           => 'nullable|string|max:20',
            'document_type_catalog_id'       => 'nullable|integer|exists:document_type_catalogs,id',
            'document_type_configuration_id' => 'nullable|integer|exists:document_type_configurations,id',
            'process_id'                     => 'nullable|integer|exists:processes,id',
            'processus'                      => 'nullable|string|max:100',
        ]);

        $siteId = (int) ($validated['site_id'] ?? ($request->header('X-Site-ID') ?: 0));
        if ($siteId <= 0) {
            return response()->json(['message' => 'Le site est requis.'], 422);
        }

        $site = Site::query()->find($siteId);
        $this->assertSiteAccess($site, $request->user());

        // Résoudre l'abréviation du type depuis les différentes sources possibles
        $typeAbbreviation = null;

        if (!empty($validated['document_type_configuration_id'])) {
            // Source 1 : ID de configuration directe
            $config = DocumentTypeConfiguration::query()->find((int) $validated['document_type_configuration_id']);
            $typeAbbreviation = $config?->abbreviation;
        } elseif (!empty($validated['document_type_catalog_id'])) {
            // Source 2 : ID du catalogue de types (DocumentTypeCatalog)
            $catalog = \App\Models\DocumentTypeCatalog::query()->find((int) $validated['document_type_catalog_id']);
            $typeAbbreviation = $catalog?->abbreviation;
        } elseif (!empty($validated['type'])) {
            // Source 3 : abréviation directe (compatibilité)
            $typeAbbreviation = strtoupper(trim((string) $validated['type']));
        }

        if (!$typeAbbreviation) {
            return response()->json(['message' => 'Le type de document est requis.'], 422);
        }

        if (!empty($validated['process_id'])) {
            $processMatchesSite = Process::query()
                ->where('id', (int) $validated['process_id'])
                ->where(function ($q) use ($siteId, $site) {
                    $q->where('site_id', $siteId)
                      ->orWhere('enterprise_id', $site->enterprise_id);
                })
                ->exists();

            if (!$processMatchesSite) {
                return response()->json([
                    'message' => 'Le processus sélectionné est invalide pour ce site.',
                ], 422);
            }
        }

        if (empty($validated['process_id'])) {
            return response()->json(['message' => 'Le processus est requis.'], 422);
        }

        if (!empty($validated['document_type_catalog_id'])) {
            $catalog = \App\Models\DocumentTypeCatalog::query()
                ->with('processes:id')
                ->find((int) $validated['document_type_catalog_id']);

            if ($catalog && $catalog->processes->isNotEmpty()) {
                $isAllowed = $catalog->processes->contains(fn ($p) => (int) $p->id === (int) $validated['process_id']);
                if (!$isAllowed) {
                    return response()->json([
                        'message' => 'Le processus sélectionné n’est pas autorisé pour ce type documentaire.',
                    ], 422);
                }
            }
        }

        try {
            $context = $this->generateUnifiedDocumentCode(
                $siteId,
                $typeAbbreviation,
                isset($validated['process_id']) ? (int) $validated['process_id'] : null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Aucune configuration de nomenclature active pour ce type.',
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'data' => [
                'code'                            => $context['code'],
                'document_type_configuration_id'  => $context['document_type_configuration_id'],
                'nomenclature_template_id'        => $context['nomenclature_template_id'],
                'nomenclature_template_version'   => $context['nomenclature_template_version'],
            ],
        ]);
    }

    public function archive(Document $document)
    {
        $document->update([
            'status' => 'obsolete',
            'archived_at' => now(),
            'archived_by' => Auth::id(),
            'is_active' => false,
        ]);

        return (new DocumentResource($document->fresh()->load(['site', 'process', 'author', 'approver', 'category'])))->response();
    }

    public function approve(Request $request, int $approval)
    {
        $validated = $request->validate([
            'action' => 'required|in:approved,rejected,changes_requested',
            'comment' => 'nullable|string|max:2000',
        ]);

        $approvalRow = DB::table('document_approvals')->where('id', $approval)->first();
        if (!$approvalRow) {
            return response()->json(['message' => 'Demande d’approbation introuvable.'], 404);
        }

        DB::table('document_approvals')
            ->where('id', $approval)
            ->update([
                'status' => $validated['action'],
                'comment' => $validated['comment'] ?? null,
                'actioned_at' => now(),
                'updated_at' => now(),
            ]);

        if ($validated['action'] === 'approved') {
            Document::query()->where('id', $approvalRow->document_id)->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        } elseif ($validated['action'] === 'rejected') {
            Document::query()->where('id', $approvalRow->document_id)->update([
                'status' => 'draft',
            ]);
        }

        return response()->json(['message' => 'Décision enregistrée.']);
    }

    public function downloadInventoryTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaire');

        $headers = ['N°', 'Type', 'Nom', 'Code', 'Version', 'État', 'Observation'];
        $sheet->fromArray($headers, null, 'A1');

        $examples = [
            [1, 'procedure', 'Gestion documentaire', 'PRC_2026_001', '1.0', 'brouillon', 'Exemple de procédure'],
            [2, 'formulaire', 'Fiche de non-conformité', '', '1.0', 'en revue', 'Le code peut être auto-généré'],
        ];
        $sheet->fromArray($examples, null, 'A2');

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1976D2'],
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'template_documents_inventory.xlsx';
        $tempPath = storage_path('app/temp_' . uniqid('doc_inventory_', true) . '.xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    public function importInventory(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:20480',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        $site = Site::find($request->integer('site_id'));
        $this->assertSiteAccess($site, $request->user());

        $path = $request->file('file')->store('imports/documents');

        try {
            /** @var DocumentInventoryImporter $importer */
            $importer = app(DocumentInventoryImporter::class);
            $results = $importer->import(storage_path('app/' . $path), (int) $site->id);

            $httpStatus = count($results['errors'] ?? []) > 0 ? 422 : 200;
            return response()->json($results, $httpStatus);
        } catch (\Throwable $e) {
            Log::error('DOCUMENT_INVENTORY_IMPORT_ERROR', [
                'site_id' => $site?->id,
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => 0,
                'errors' => [
                    [
                        'line' => 0,
                        'message' => 'Erreur lors de l\'import de l\'inventaire documentaire.',
                    ],
                ],
                'created' => [],
                'message' => 'Échec de l\'import',
            ], 500);
        } finally {
            Storage::delete($path);
        }
    }

    public function exportInventory(Request $request)
    {
        $validated = $request->validate([
            'site_id'                    => 'required|exists:sites,id',
            'document_type_catalog_id'   => 'required|exists:document_type_catalogs,id',
            'process_id'                 => 'required|exists:processes,id',
        ]);

        $catalog = \App\Models\DocumentTypeCatalog::with('processes:id')->findOrFail((int) $validated['document_type_catalog_id']);

        if ($catalog->processes->isNotEmpty()) {
            $allowed = $catalog->processes->contains(fn ($p) => (int) $p->id === (int) $validated['process_id']);
            if (!$allowed) {
                return response()->json(['message' => 'Le processus sélectionné n\'est pas autorisé pour ce type documentaire.'], 422);
            }
        }

        $documents = Document::query()
            ->where('site_id', (int) $validated['site_id'])
            ->where('process_id', (int) $validated['process_id'])
            ->whereHas('typeConfiguration', function ($q) use ($catalog) {
                $q->where('abbreviation', $catalog->abbreviation);
            })
            ->where('workflow_status', 'approved')
            ->get(['code', 'title', 'version', 'status', 'workflow_status', 'approved_at', 'description']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaire');

        $headers = ['Code', 'Titre', 'Version', 'Statut', 'Date approbation', 'Description'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($documents as $doc) {
            $sheet->fromArray([
                $doc->code,
                $doc->title,
                $doc->version,
                $doc->workflow_status,
                $doc->approved_at?->format('Y-m-d'),
                $doc->description,
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
        ]);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempPath = storage_path('app/temp_inv_' . uniqid('', true) . '.xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, 'inventaire_documents.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportByLevel(string $level)
    {
        return response()->json([
            'message' => 'Export pyramide disponible dans un prochain incrément.',
            'level' => $level,
        ], 501);
    }

    public function exportCompletePyramid()
    {
        return response()->json([
            'message' => 'Export pyramide complet disponible dans un prochain incrément.',
        ], 501);
    }

    /**
     * Retourne les statistiques de l'inventaire documentaire.
     * Accepte les mêmes filtres que index() : site_id, module, workflow_status, type, search.
     */
    public function getStats(Request $request)
    {
        $query = Document::query();
        $this->scopeDocumentQuery($query, $request->user());

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }
        if ($request->filled('module')) {
            $query->where('source_module', $request->string('module'));
        }
        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->string('workflow_status'));
        }
        if ($request->filled('type')) {
            $query->whereHas('typeConfiguration', fn ($q) => $q->where('abbreviation', strtoupper($request->string('type'))));
        }
        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(fn ($inner) => $inner->where('code', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"));
        }

        $total    = (clone $query)->count();
        $pending  = (clone $query)->whereIn('workflow_status', ['pending_verification', 'pending_approval'])->count();
        $approved = (clone $query)->where('workflow_status', 'approved')->count();
        $aReviser = (clone $query)->whereNotNull('review_due_date')->where('review_due_date', '<', now())->count();

        $parType = (clone $query)
            ->leftJoin('document_type_configurations', 'documents.document_type_configuration_id', '=', 'document_type_configurations.id')
            ->selectRaw('COALESCE(document_type_configurations.abbreviation, documents.source_type, \'other\') as type, count(*) as count')
            ->groupByRaw('COALESCE(document_type_configurations.abbreviation, documents.source_type, \'other\')')
            ->pluck('count', 'type')
            ->map(fn ($c, $t) => ['type' => $t, 'count' => $c])
            ->values();

        $parStatut = (clone $query)
            ->selectRaw('workflow_status as statut, count(*) as count')
            ->groupBy('workflow_status')
            ->pluck('count', 'statut')
            ->map(fn ($c, $s) => ['statut' => $s, 'count' => $c])
            ->values();

        return response()->json([
            'total'      => $total,
            'pending'    => $pending,
            'approved'   => $approved,
            'a_reviser'  => $aReviser,
            'par_type'   => $parType,
            'par_statut' => $parStatut,
            'par_etat'   => [],
        ]);
    }

    public function getPyramideStats()
    {
        $stats = DocumentCategory::query()
            ->select('level', DB::raw('count(*) as categories_count'))
            ->groupBy('level')
            ->orderBy('level')
            ->get();

        return response()->json(['data' => $stats]);
    }

    private function mergeSourceContext(array $metadata, array $input): array
    {
        $module    = trim((string) ($input['source_module']    ?? ''));
        $submodule = trim((string) ($input['source_submodule'] ?? ''));
        $section   = trim((string) ($input['source_section']   ?? ''));

        if ($module === '' && $submodule === '' && $section === '') {
            return $metadata;
        }

        $existing = (array) ($metadata['source_context'] ?? []);
        if ($module    !== '') $existing['module']    = $module;
        if ($submodule !== '') $existing['submodule'] = $submodule;
        if ($section   !== '') $existing['section']   = $section;

        $metadata['source_context'] = $existing;
        return $metadata;
    }

    /**
     * Traduit les champs legacy (nom, processus, etat, statut, fichier, date_creation)
     * en champs modernes (title, status, file…) avant validation.
     * Permet d'accepter les deux formats sans branche conditionnelle.
     */
    private function normalizeLegacyFields(Request $request): void
    {
        $data = $request->all();
        $changed = false;

        // nom → title
        if (isset($data['nom']) && !isset($data['title'])) {
            $data['title'] = $data['nom'];
            $changed = true;
        }

        // etat reste tel quel (champ Document moderne)
        // statut → ignoré (remplacé par workflow_status)

        // fichier → file (pour la validation unifiée)
        // On garde les deux, la méthode store() gère les deux

        // date_creation → ignoré (on utilise created_at automatique)

        // processus → processus (déjà dans fillable Document)

        if ($changed) {
            $request->replace($data);
        }
    }

    /**
     * Soumet automatiquement un document au workflow après création.
     * Transition : draft → pending_verification (si needs_verification)
     *           ou draft → pending_approval (sinon)
     */
    private function submitToWorkflow(Document $document, ?User $user): void
    {
        try {
            $needsVerification = (bool) $document->needs_verification;
            $nextState = $needsVerification ? 'pending_verification' : 'pending_approval';

            \Illuminate\Support\Facades\DB::table('documents')
                ->where('id', $document->id)
                ->where(function ($q) {
                    $q->where('workflow_status', 'draft')->orWhereNull('workflow_status');
                })
                ->update([
                    'workflow_status' => $nextState,
                    'updated_at'      => now(),
                ]);

            $document->workflow_status = $nextState;

            // Notifier les vérificateurs/approbateurs
            $permission = $needsVerification ? 'verify_documents' : 'approve_documents';
            $notifType  = $needsVerification ? 'verification_request' : 'approval_request';

            $recipients = $this->findWorkflowUsersByPermission($permission, $document, $user?->id);
            foreach ($recipients as $recipient) {
                $recipient->notify(new DocumentWorkflowNotification($notifType, $document, $user));
            }

            // Log immutable
            app(DocumentWorkflowAuditTrailService::class)->recordEvent(
                document: $document,
                eventType: 'auto_submitted',
                fromStatus: 'draft',
                toStatus: $nextState,
                actorUserId: $user?->id,
                metadata: ['auto_submit' => true, 'needs_verification' => $needsVerification],
            );
        } catch (\Exception $e) {
            // Ne pas bloquer la création si le workflow échoue
            Log::warning('submitToWorkflow failed', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function isHiddenDraftModification(Document $document, $user): bool
    {
        $isDraftModification = (string) ($document->workflow_status ?? 'draft') === 'draft'
            && (bool) data_get($document->metadata, 'versioning.base_approved_version');

        if (!$isDraftModification) {
            return false;
        }

        $draftOwnerId = (int) (data_get($document->metadata, 'versioning.draft_started_by') ?: $document->author_id);

        return $draftOwnerId !== (int) $user->id;
    }

    /**
     * A modification owner may edit only the documents explicitly attached to
     * an active system-modification request. Other users keep the normal
     * document permissions and workflow rules.
     */
    private function assertModificationDocumentAccess(Document $document, ?User $user): void
    {
        if (!$user || $user->user_type === 'super_admin' || $user->isEnterpriseAdmin()) {
            return;
        }

        if (!Schema::hasColumn('modifications', 'document_ids')) {
            return;
        }

        $requests = Modification::query()
            ->where('responsible_id', $user->id)
            ->whereIn('workflow_status', ['brouillon', 'verifie_rq'])
            ->whereHas('site', fn ($query) => $query->where('enterprise_id', $user->enterprise_id))
            ->get(['document_ids']);

        if ($requests->isEmpty()) {
            return;
        }

        $allowedDocumentIds = $requests
            ->flatMap(fn (Modification $modification) => (array) ($modification->document_ids ?? []))
            ->map(fn ($id) => (int) $id)
            ->unique();

        abort_unless($allowedDocumentIds->contains((int) $document->id), 403, 'Ce document ne fait pas partie de votre demande de modification.');
    }

    private function scopeDocumentQuery($query, $user): void
    {
        if (!$user || $user->user_type === 'super_admin') {
            return;
        }

        if (!empty($user->site_id)) {
            $query->where('site_id', $user->site_id);
            return;
        }

        $siteIds = Site::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->pluck('id')
            ->all();

        if (empty($siteIds)) {
            $query->whereRaw('1=0');
            return;
        }

        $query->whereIn('site_id', $siteIds);
    }

    private function applyGeneratedDraftVisibilityScope($query, $user): void
    {
        if (!$user || $user->user_type === 'super_admin') {
            return;
        }

        if ($user->can('verify_documents') || $user->can('approve_documents')) {
            return;
        }

        $query->where(function ($inner) use ($user) {
            $inner->whereNull('module_type')
                ->orWhere('module_type', '!=', 'generated')
                ->orWhereNull('workflow_status')
                ->orWhere('workflow_status', '!=', 'draft')
                ->orWhere('author_id', $user->id);
        });
    }

    private function assertGeneratedDraftAccess(Document $document, $user): void
    {
        if (!$user || $user->user_type === 'super_admin') {
            return;
        }

        if ((string) ($document->module_type ?? '') !== 'generated') {
            return;
        }

        if ((string) ($document->workflow_status ?? '') !== 'draft') {
            return;
        }

        if ($document->author_id && (int) $document->author_id === (int) $user->id) {
            return;
        }

        if ($user->can('verify_documents') || $user->can('approve_documents')) {
            return;
        }

        abort(403, 'Accès non autorisé.');
    }

    private function findWorkflowUsersByPermission(string $permission, Document $document, ?int $excludeUserId = null)
    {
        $query = User::query()
            ->where('is_active', true)
            ->permission($permission);

        if ($document->site_id) {
            $query->where('site_id', $document->site_id);
        } elseif ($document->site?->enterprise_id) {
            $query->where('enterprise_id', $document->site->enterprise_id);
        }

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $results = $query->get();

        // Fallback : si aucun destinataire trouvé, inclure l'auteur s'il a la permission
        if ($results->isEmpty() && $excludeUserId) {
            $author = User::query()
                ->where('id', $excludeUserId)
                ->where('is_active', true)
                ->permission($permission)
                ->first();
            if ($author) {
                $results = collect([$author]);
            }
        }

        return $results;
    }

    private function assertSiteAccess(?Site $site, $user): void
    {
        if (!$user || $user->user_type === 'super_admin') {
            return;
        }

        if (!empty($user->site_id) && $site && (int) $site->id === (int) $user->site_id) {
            return;
        }

        if (!$site || (int) $site->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }
    }

    /**
     * Get active norms for the current site (for inventory filtering).
     */
    public function getActiveNorms(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $site = $user->site;
        if (!$site || !$site->enterprise) {
            return response()->json(['data' => []]);
        }

        // Get active subscriptions for the enterprise
        $norms = $site->enterprise
            ->subscriptions()
            ->where('status', 'active')
            ->with('offer.norms')
            ->get()
            ->flatMap(function ($subscription) {
                return $subscription->offer?->norms ?? collect();
            })
            ->unique('id')
            ->values();

        return response()->json([
            'data' => $norms->map(fn ($norm) => [
                'id' => $norm->id,
                'code' => $norm->code,
                'name' => $norm->name,
                'full_name' => "{$norm->code} - {$norm->name}",
            ]),
        ]);
    }
}
