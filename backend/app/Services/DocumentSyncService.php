<?php

namespace App\Services;

use App\Models\Document;
use App\Models\QhsePolicy;
use App\Support\DocumentSourceTypeMap;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service de synchronisation de l'inventaire documentaire.
 * Synchronise les documents générés (PDF, DOCX) avec l'inventaire documentaire.
 */
class DocumentSyncService
{
    /**
     * Synchronise un document créé/modifié avec l'inventaire
     */
    public function syncDocument(Document $document, array $data = [], ?int $siteId = null, ?int $authorId = null): void
    {
        // Synchronisation de l'inventaire documentaire
        // Implémentation future: indexer le document dans l'inventaire centralisé
    }

    /**
     * Synchronise une politique QHSE avec l'inventaire
     */
    public function syncPolicy(QhsePolicy $policy, ?int $userId = null): void
    {
        // Synchronisation de la politique dans l'inventaire documentaire
    }

    /**
     * Synchronise un document généré (rapport, export) avec l'inventaire
     */
    public function syncGeneratedProcessDocument(array $params): Document
    {
        return $this->upsertGeneratedProcessDocument($params);
    }

    public function upsertGeneratedProcessDocument(array $params): Document
    {
        $siteId = (int) ($params['site_id'] ?? 0);
        $processId = isset($params['process_id']) ? (int) $params['process_id'] : null;
        $documentKind = (string) ($params['document_kind'] ?? 'generated_document');
        $type = (string) ($params['type'] ?? 'ENR');
        $sourceType = (string) ($params['source_type'] ?? $documentKind);
        $sourceModelId = isset($params['source_id'])
            ? (string) $params['source_id']
            : ($processId ? (string) $processId : (string) $siteId);

        $forceNew = (bool) ($params['force_new'] ?? false);
        $storeFile = (bool) ($params['store_file'] ?? false);
        $documentId = isset($params['document_id']) ? (int) $params['document_id'] : null;
        $document = null;
        if ($documentId) {
            $document = Document::query()->find($documentId);
        }
        if (!$document) {
            // Toujours rechercher un brouillon existant non validé pour éviter la duplication inutile
            $document = Document::query()
                ->where('site_id', $siteId)
                ->where(function ($q) {
                    $q->whereNull('workflow_status')
                      ->orWhere('workflow_status', '!=', 'approved');
                })
                ->where('metadata->source', 'generated_process_document')
                ->where('metadata->document_kind', $documentKind)
                ->when($processId, fn ($query) => $query->where('process_id', $processId))
                ->latest('id')
                ->first();
        }

        $storedFilePath = null;
        if (!empty($params['file_source_path'])) {
            $sourcePath = (string) $params['file_source_path'];
            if ($storeFile) {
                $storedFilePath = $this->storeGeneratedFile($sourcePath, $documentKind, $siteId);
            } else {
                $storedFilePath = $sourcePath;
            }
        }

        $isNew = false;
        if (!$document) {
            $isNew = true;
            $generation = app(DocumentCodeGenerationService::class)
                ->generateForSiteAndType($siteId, $type, $processId, null, $documentKind);

            $sourceMap = DocumentSourceTypeMap::resolve($sourceType);

            $document = Document::query()->create([
                'site_id' => $siteId,
                'enterprise_id' => \App\Models\Site::query()->find($siteId)?->enterprise_id,
                'process_id' => $processId,
                'document_type_configuration_id' => $generation['document_type_configuration_id'],
                'nomenclature_template_id' => $generation['nomenclature_template_id'],
                'nomenclature_template_version' => $generation['nomenclature_template_version'],
                'ref' => 'DOC-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                'code' => $generation['code'],
                'code_status' => 'reserved',
                'title' => (string) ($params['title'] ?? 'Document genere'),
                'description' => $params['description'] ?? null,
                'version' => '1.0',
                'file_path' => $storedFilePath,
                'status' => 'draft',
                'workflow_status' => 'draft',
                'needs_verification' => true,
                'source_type' => 'generated',
                'source_module' => $sourceMap['module'],
                'source_submodule' => $sourceMap['submodule'],
                'source_section' => $sourceMap['section'] ?: null,
                'module_type' => 'generated',
                'module_id' => is_numeric($sourceModelId) ? (int) $sourceModelId : null,
                'author_id' => $params['created_by'] ?? 1,
                'metadata' => [
                    'source' => 'generated_process_document',
                    'source_id' => $processId,
                    'source_type' => $sourceType,
                    'source_model_id' => $sourceModelId,
                    'source_updated_at' => $params['source_updated_at'] ?? null,
                    'document_kind' => $documentKind,
                    'process_code' => $params['process_code'] ?? null,
                    'process_name' => $params['process_name'] ?? null,
                    ...((array) ($params['metadata'] ?? [])),
                ],
            ]);
        }

        $document->fill([
            'title' => (string) ($params['title'] ?? $document->title),
            'description' => $params['description'] ?? $document->description,
            'process_id' => $processId,
            'status' => 'draft',
            'workflow_status' => 'draft',
            'needs_verification' => true,
            'module_type' => 'generated',
            'module_id' => is_numeric($sourceModelId) ? (int) $sourceModelId : $document->module_id,
        ]);

        $metadata = (array) ($document->metadata ?? []);
        $document->metadata = array_merge($metadata, [
            'source' => 'generated_process_document',
            'source_type' => $sourceType,
            'source_model_id' => $sourceModelId,
            'source_updated_at' => $params['source_updated_at'] ?? ($metadata['source_updated_at'] ?? null),
            'document_kind' => $documentKind,
        ]);

        if ($storedFilePath) {
            if ($storeFile && $document->file_path && $document->file_path !== $storedFilePath) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->file_path = $storedFilePath;
        }

        $document->save();

        // Journaliser la génération ou mise à jour du brouillon
        \App\Models\DocumentWorkflowHistory::logAction(
            documentId: $document->id,
            userId: $params['created_by'] ?? $document->author_id ?? 1,
            action: $isNew ? 'generated' : 'regenerated',
            fromStatus: $isNew ? null : 'draft',
            toStatus: 'draft',
            comment: $isNew ? 'Brouillon généré automatiquement.' : 'Brouillon mis à jour et régénéré.'
        );

        if ($storeFile && !empty($params['file_source_path'])) {
            $sourcePath = (string) $params['file_source_path'];
            if (is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }

        return $document;
    }

    private function storeGeneratedFile(string $filePath, string $documentKind, int $siteId): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'dat';
        $safeKind = Str::slug($documentKind, '_');
        $fileName = sprintf('%s_%s_%s.%s', $safeKind, $siteId, now()->format('YmdHis'), $extension);
        $targetPath = sprintf('documents/generated/%s/%s', $safeKind, $fileName);

        $disk = Storage::disk('public');
        $stream = fopen($filePath, 'rb');
        $disk->put($targetPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $targetPath;
    }
}
