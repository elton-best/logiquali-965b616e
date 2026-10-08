<?php

namespace App\Modules\Support\Services;

use App\Models\CodeStructurePart;

use App\Models\DocumentTypeConfiguration;
use App\Models\CodeSequence;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class CodeGenerationService
{
    private function isApprovedDocument(Document $document): bool
    {
        return ((string) ($document->workflow_status ?? '') === 'approved')
            || ((string) ($document->status ?? '') === 'approved');
    }
    /**
     * Génère un code complet pour un document
     *
     * @param int $typeConfigId
     * @param array $context ['site_id' => 1, 'process_id' => 2, 'year' => 2026, 'month' => 4]
     * @return array ['code' => string, 'sequence_id' => int]
     */
    public function generateCode(int $typeConfigId, array $context): array
    {
        $config = DocumentTypeConfiguration::with('structureParts')->findOrFail($typeConfigId);
        
        // Valider que le contexte contient les informations nécessaires
        if (!isset($context['site_id']) || !isset($context['enterprise_id'])) {
            throw new \InvalidArgumentException('Le contexte doit contenir site_id et enterprise_id');
        }

        return DB::transaction(function () use ($config, $context) {
            $codeParts = [];
            $sequenceId = null;

            foreach ($config->structureParts as $part) {
                if ($part->isSequence()) {
                    // Récupérer ou créer la séquence
                    $sequence = $this->getOrCreateSequence($config, $part, $context);
                    $sequenceId = $sequence->id;
                    
                    // Obtenir le prochain numéro
                    $nextNumber = $sequence->getNextNumber();
                    
                    // Formater avec padding
                    $value = str_pad((string) $nextNumber, $part->part_length ?? 3, '0', STR_PAD_LEFT);
                } else {
                    // Résoudre la valeur de la partie
                    $value = $part->resolveValue($context);
                }

                if ($value !== null) {
                    $codeParts[] = $value;
                    
                    if ($part->separator_after) {
                        $codeParts[] = $part->separator_after;
                    }
                }
            }

            // Assembler le code final
            $code = implode('', $codeParts);
            $code = rtrim($code, '-_./'); // Retirer le dernier séparateur si présent

            return [
                'code' => $code,
                'sequence_id' => $sequenceId,
            ];
        });
    }

    /**
     * Récupère ou crée une séquence selon le scope
     *
     * @param DocumentTypeConfiguration $config
     * @param \App\Models\CodeStructurePart $sequencePart
     * @param array $context
     * @return CodeSequence
     */
    private function getOrCreateSequence(
        DocumentTypeConfiguration $config,
        $sequencePart,
        array $context
    ): CodeSequence {
        $params = [
            'enterprise_id' => $context['enterprise_id'],
            'site_id' => $context['site_id'],
            'document_type_configuration_id' => $config->id,
        ];

        // Ajouter les paramètres selon le scope
        $scope = $sequencePart->sequence_scope;

        if (str_contains($scope, 'process') && isset($context['process_id'])) {
            $params['process_id'] = $context['process_id'];
        }

        if (str_contains($scope, 'year')) {
            $params['year'] = $context['year'] ?? date('Y');
        }

        if (str_contains($scope, 'month')) {
            $params['month'] = $context['month'] ?? date('m');
        }

        return CodeSequence::findOrCreateForScope($params);
    }

    /**
     * Libère un code pour le rendre disponible au recyclage
     *
     * @param string $code
     * @param int $typeConfigId
     * @return bool
     */
    public function releaseCode(string $code, int $typeConfigId): bool
    {
        $document = Document::where('code', $code)
            ->where('document_type_configuration_id', $typeConfigId)
            ->first();

        if (!$document || $document->code_status === 'released') {
            return false;
        }
        if ($this->isApprovedDocument($document)) {
            return false;
        }

        return DB::transaction(function () use ($document) {
            // Extraire le numéro de séquence du code
            $sequenceNumber = $this->extractSequenceNumber($document);

            if ($sequenceNumber === null) {
                return false;
            }

            // Trouver la séquence correspondante
            $sequence = $this->findSequenceForDocument($document);

            if ($sequence) {
                // Libérer le numéro
                $sequence->releaseNumber($sequenceNumber);
            }

            // Marquer le code comme libéré
            $document->update(['code_status' => 'released']);

            return true;
        });
    }

    /**
     * Extrait le numéro de séquence d'un code de document
     *
     * @param Document $document
     * @return int|null
     */
    private function extractSequenceNumber(Document $document): ?int
    {
        if (!$document->typeConfiguration) {
            return null;
        }

        $sequencePart = $document->typeConfiguration->getSequencePart();
        
        if (!$sequencePart) {
            return null;
        }

        // Construire un pattern regex pour extraire la séquence
        $pattern = '';
        foreach ($document->typeConfiguration->structureParts as $part) {
            if ($part->isSequence()) {
                $pattern .= '(\d{' . ($part->part_length ?? 3) . '})';
            } else {
                $pattern .= '[^' . preg_quote($part->separator_after ?? '-', '/') . ']+';
            }
            
            if ($part->separator_after) {
                $pattern .= preg_quote($part->separator_after, '/');
            }
        }

        if (preg_match('/' . $pattern . '/', $document->code, $matches)) {
            return isset($matches[1]) ? (int) $matches[1] : null;
        }

        return null;
    }

    /**
     * Trouve la séquence correspondant à un document
     *
     * @param Document $document
     * @return CodeSequence|null
     */
    private function findSequenceForDocument(Document $document): ?CodeSequence
    {
        if (!$document->typeConfiguration) {
            return null;
        }

        $sequencePart = $document->typeConfiguration->getSequencePart();
        
        if (!$sequencePart) {
            return null;
        }

        $params = [
            'enterprise_id' => $document->site->enterprise_id,
            'site_id' => $document->site_id,
            'document_type_configuration_id' => $document->document_type_configuration_id,
        ];

        $scope = $sequencePart->sequence_scope;

        if (str_contains($scope, 'process') && $document->process_id) {
            $params['process_id'] = $document->process_id;
        }

        if (str_contains($scope, 'year')) {
            $params['year'] = $document->created_at->year;
        }

        if (str_contains($scope, 'month')) {
            $params['month'] = $document->created_at->month;
        }

        return CodeSequence::where($params)->first();
    }

    /**
     * Réserve un code temporairement (avant validation finale)
     *
     * @param string $code
     * @param int $documentId
     * @return bool
     */
    public function reserveCode(string $code, int $documentId): bool
    {
        $document = Document::findOrFail($documentId);
        if ($this->isApprovedDocument($document)) {
            return false;
        }

        return $document->update([
            'code' => $code,
            'code_status' => 'reserved',
        ]);
    }

    /**
     * Active un code réservé (après validation)
     *
     * @param string $code
     * @param int $typeConfigId
     * @return bool
     */
    public function activateCode(string $code, int $typeConfigId): bool
    {
        $document = Document::where('code', $code)
            ->where('document_type_configuration_id', $typeConfigId)
            ->first();

        if (!$document || $document->code_status !== 'reserved') {
            return false;
        }
        if ($this->isApprovedDocument($document)) {
            return false;
        }

        return $document->update(['code_status' => 'active']);
    }

    /**
     * Vérifie si un code est disponible
     *
     * @param string $code
     * @param int|null $excludeDocumentId
     * @return bool
     */
    public function isCodeAvailable(string $code, ?int $excludeDocumentId = null): bool
    {
        $query = Document::where('code', $code)
            ->whereIn('code_status', ['active', 'reserved']);

        if ($excludeDocumentId) {
            $query->where('id', '!=', $excludeDocumentId);
        }

        return !$query->exists();
    }
}
