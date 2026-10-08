<?php

namespace Tests\Traits;

use App\Models\Document;
use App\Models\DocumentTypeConfiguration;
use App\Models\Site;
use App\Models\User;
use App\Models\Process;

trait CreatesValidDocuments
{
    /**
     * Crée un document valide avec toutes les contraintes respectées
     */
    protected function createValidDocument(array $attributes = []): Document
    {
        $defaults = [
            'site_id' => Site::factory(),
            'process_id' => Process::factory(),
            'document_type_configuration_id' => DocumentTypeConfiguration::factory(),
            'status' => 'draft', // Valide: draft, pending_approval, approved, obsolete
            'code' => 'DOC-' . rand(1000, 9999),
            'ref' => 'REF-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'title' => 'Test Document ' . rand(1000, 9999),
            'description' => 'Test description',
            'version' => '1.0',
            'author_id' => User::factory(),
        ];

        return Document::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * Crée un document avec une configuration de type spécifique
     */
    protected function createDocumentWithTypeConfig(DocumentTypeConfiguration $typeConfig, array $attributes = []): Document
    {
        return $this->createValidDocument(array_merge(['document_type_configuration_id' => $typeConfig->id], $attributes));
    }

    /**
     * Crée un document avec un status spécifique
     */
    protected function createDocumentWithStatus(string $status, array $attributes = []): Document
    {
        $validStatuses = ['draft', 'pending_approval', 'approved', 'obsolete'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid document status: $status. Valid statuses: " . implode(', ', $validStatuses));
        }

        return $this->createValidDocument(array_merge(['status' => $status], $attributes));
    }

    /**
     * Crée plusieurs documents valides
     */
    protected function createValidDocuments(int $count, array $attributes = []): \Illuminate\Support\Collection
    {
        return collect(range(1, $count))->map(function () use ($attributes) {
            return $this->createValidDocument($attributes);
        });
    }
}
