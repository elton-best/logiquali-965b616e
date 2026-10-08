<?php

namespace App\Services;

use App\Models\DocumentTypeConfiguration;
use App\Models\CodeStructurePart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentTypeConfigurationService
{
    /**
     * Crée une nouvelle configuration de type de document
     *
     * @param array $data
     * @return DocumentTypeConfiguration
     * @throws ValidationException
     */
    public function createConfiguration(array $data): DocumentTypeConfiguration
    {
        // Valider la structure avant création
        if (isset($data['structure_parts'])) {
            $validation = $this->validateStructure($data['structure_parts']);
            if (!$validation['valid']) {
                throw ValidationException::withMessages([
                    'structure_parts' => $validation['errors'],
                ]);
            }
        }

        return DB::transaction(function () use ($data) {
            // Créer la configuration
            $config = DocumentTypeConfiguration::create([
                'enterprise_id' => $data['enterprise_id'],
                'site_id' => $data['site_id'] ?? null,
                'name' => $data['name'],
                'abbreviation' => strtoupper($data['abbreviation']),
                'abbreviation_length' => $data['abbreviation_length'] ?? 3,
                'scope' => $data['scope'] ?? 'site',
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
            ]);

            // Créer les parties de la structure
            if (isset($data['structure_parts'])) {
                foreach ($data['structure_parts'] as $index => $partData) {
                    CodeStructurePart::create([
                        'document_type_configuration_id' => $config->id,
                        'part_order' => $partData['part_order'] ?? ($index + 1),
                        'part_name' => $partData['part_name'],
                        'part_type' => $partData['part_type'],
                        'part_length' => $partData['part_length'] ?? null,
                        'separator_after' => $partData['separator_after'] ?? null,
                        'is_required' => $partData['is_required'] ?? true,
                        'default_value' => $partData['default_value'] ?? null,
                        'sequence_scope' => $partData['sequence_scope'] ?? null,
                    ]);
                }
            }

            return $config->load('structureParts');
        });
    }

    /**
     * Met à jour une configuration existante
     *
     * @param int $id
     * @param array $data
     * @return DocumentTypeConfiguration
     * @throws ValidationException
     */
    public function updateConfiguration(int $id, array $data): DocumentTypeConfiguration
    {
        $config = DocumentTypeConfiguration::findOrFail($id);

        // Vérifier s'il y a des documents utilisant cette configuration
        if ($config->documents()->exists() && isset($data['structure_parts'])) {
            throw ValidationException::withMessages([
                'structure_parts' => ['Impossible de modifier la structure : des documents utilisent déjà cette configuration.'],
            ]);
        }

        // Valider la nouvelle structure si fournie
        if (isset($data['structure_parts'])) {
            $validation = $this->validateStructure($data['structure_parts']);
            if (!$validation['valid']) {
                throw ValidationException::withMessages([
                    'structure_parts' => $validation['errors'],
                ]);
            }
        }

        return DB::transaction(function () use ($config, $data) {
            // Mettre à jour la configuration
            $config->update([
                'name' => $data['name'] ?? $config->name,
                'abbreviation' => isset($data['abbreviation']) ? strtoupper($data['abbreviation']) : $config->abbreviation,
                'abbreviation_length' => $data['abbreviation_length'] ?? $config->abbreviation_length,
                'scope' => $data['scope'] ?? $config->scope,
                'is_active' => $data['is_active'] ?? $config->is_active,
                'description' => $data['description'] ?? $config->description,
            ]);

            // Mettre à jour les parties si fournies
            if (isset($data['structure_parts'])) {
                // Supprimer les anciennes parties
                $config->structureParts()->delete();

                // Créer les nouvelles parties
                foreach ($data['structure_parts'] as $index => $partData) {
                    CodeStructurePart::create([
                        'document_type_configuration_id' => $config->id,
                        'part_order' => $partData['part_order'] ?? ($index + 1),
                        'part_name' => $partData['part_name'],
                        'part_type' => $partData['part_type'],
                        'part_length' => $partData['part_length'] ?? null,
                        'separator_after' => $partData['separator_after'] ?? null,
                        'is_required' => $partData['is_required'] ?? true,
                        'default_value' => $partData['default_value'] ?? null,
                        'sequence_scope' => $partData['sequence_scope'] ?? null,
                    ]);
                }
            }

            return $config->fresh(['structureParts']);
        });
    }

    /**
     * Supprime une configuration
     *
     * @param int $id
     * @return bool
     * @throws ValidationException
     */
    public function deleteConfiguration(int $id): bool
    {
        $config = DocumentTypeConfiguration::findOrFail($id);

        // Vérifier s'il y a des documents utilisant cette configuration
        if ($config->documents()->exists()) {
            throw ValidationException::withMessages([
                'configuration' => ['Impossible de supprimer : des documents utilisent cette configuration.'],
            ]);
        }

        return DB::transaction(function () use ($config) {
            // Supprimer les parties (cascade automatique)
            $config->structureParts()->delete();
            
            // Supprimer les séquences associées
            $config->sequences()->delete();
            
            // Supprimer la configuration
            return $config->delete();
        });
    }

    /**
     * Valide la cohérence d'une structure de code
     *
     * @param array $parts
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStructure(array $parts): array
    {
        $errors = [];
        $hasSequence = false;
        $orders = [];
        $sequenceOrders = [];

        if (empty($parts)) {
            $errors[] = 'La structure doit contenir au moins une partie.';
            return ['valid' => false, 'errors' => $errors];
        }

        foreach ($parts as $index => $part) {
            $partOrder = $part['part_order'] ?? ($index + 1);
            
            // Vérifier les doublons d'ordre
            if (in_array($partOrder, $orders)) {
                $errors[] = "L'ordre {$partOrder} est utilisé plusieurs fois.";
            }
            $orders[] = $partOrder;

            // Vérifier la présence d'une séquence
            if ($part['part_type'] === 'sequence') {
                $hasSequence = true;
                $sequenceOrders[] = $partOrder;
                
                if (empty($part['sequence_scope'])) {
                    $errors[] = "La partie séquence (ordre {$partOrder}) doit avoir un scope défini.";
                }
                
                if (empty($part['part_length']) || $part['part_length'] < 1) {
                    $errors[] = "La partie séquence (ordre {$partOrder}) doit avoir une longueur définie (minimum 1).";
                }
            }

            // Vérifier les champs obligatoires
            if (empty($part['part_name'])) {
                $errors[] = "La partie {$partOrder} doit avoir un nom.";
            }

            if (empty($part['part_type'])) {
                $errors[] = "La partie {$partOrder} doit avoir un type.";
            }

            // Vérifier la cohérence des types
            if ($part['part_type'] === 'free_text' && ($part['is_required'] ?? false)) {
                $errors[] = "La partie texte libre (ordre {$partOrder}) ne peut pas être obligatoire.";
            }
        }

        if (!$hasSequence) {
            $errors[] = 'La structure doit contenir au moins une partie de type séquence.';
        }

        if ($hasSequence) {
            $maxOrder = !empty($orders) ? max($orders) : null;
            foreach ($sequenceOrders as $sequenceOrder) {
                if ($maxOrder !== null && $sequenceOrder !== $maxOrder) {
                    $errors[] = 'La partie séquence doit être en dernière position.';
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Génère un aperçu du code selon une configuration
     *
     * @param int $configId
     * @param array $context
     * @return string
     */
    public function previewCode(int $configId, array $context = []): string
    {
        $config = DocumentTypeConfiguration::with('structureParts')->findOrFail($configId);
        
        // Ajouter des valeurs par défaut pour la prévisualisation
        $context = array_merge([
            'year' => date('Y'),
            'month' => date('m'),
            'day' => date('d'),
        ], $context);

        return $config->generatePreviewCode($context);
    }

    /**
     * Duplique une configuration existante
     *
     * @param int $id
     * @param array $overrides
     * @return DocumentTypeConfiguration
     */
    public function duplicateConfiguration(int $id, array $overrides = []): DocumentTypeConfiguration
    {
        $original = DocumentTypeConfiguration::with('structureParts')->findOrFail($id);

        return DB::transaction(function () use ($original, $overrides) {
            // Créer la copie
            $copy = DocumentTypeConfiguration::create([
                'enterprise_id' => $overrides['enterprise_id'] ?? $original->enterprise_id,
                'site_id' => $overrides['site_id'] ?? $original->site_id,
                'name' => $overrides['name'] ?? ($original->name . ' (Copie)'),
                'abbreviation' => $overrides['abbreviation'] ?? ($original->abbreviation . '2'),
                'abbreviation_length' => $original->abbreviation_length,
                'scope' => $overrides['scope'] ?? $original->scope,
                'is_active' => false, // Désactivée par défaut
                'description' => $overrides['description'] ?? $original->description,
            ]);

            // Copier les parties
            foreach ($original->structureParts as $part) {
                CodeStructurePart::create([
                    'document_type_configuration_id' => $copy->id,
                    'part_order' => $part->part_order,
                    'part_name' => $part->part_name,
                    'part_type' => $part->part_type,
                    'part_length' => $part->part_length,
                    'separator_after' => $part->separator_after,
                    'is_required' => $part->is_required,
                    'default_value' => $part->default_value,
                    'sequence_scope' => $part->sequence_scope,
                ]);
            }

            return $copy->load('structureParts');
        });
    }
}
