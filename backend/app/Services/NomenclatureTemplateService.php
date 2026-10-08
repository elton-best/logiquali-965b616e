<?php

namespace App\Services;

use App\Models\CodeStructurePart;
use App\Models\DocumentTypeCatalog;
use App\Models\DocumentTypeConfiguration;
use App\Models\NomenclatureTemplate;
use App\Models\DocumentCodePool;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NomenclatureTemplateService
{
    /**
     * Valider la structure du format (format flexible)
     */
    public function validateFormatStructure(array $structure): array
    {
        // Accepter les deux formats:
        // 1. Ancien: {order, type, label, length, editable}
        // 2. Nouveau: {type: 'token'|'separator', token: 'SEQUENCE'|..., value: '-'}
        $hasSequence = collect($structure)->contains(function ($part) {
            $type = $part['type'] ?? '';
            $token = $part['token'] ?? '';
            // Format nouveau
            if ($type === 'token' && in_array($token, ['SEQUENCE', 'NUMERO', 'SEQ'])) {
                return true;
            }
            // Format ancien
            if ($type === 'sequential_number') {
                return true;
            }
            return false;
        });

        if (!$hasSequence) {
            throw ValidationException::withMessages([
                'format_structure' => ['La structure doit contenir un token de séquence (SEQUENCE).'],
            ]);
        }

        return $structure;
    }

    /**
     * Générer un exemple de code basé sur la structure
     */
    public function generatePreviewExample(array $structure, string $separator = '-'): string
    {
        $hasExplicitSeparators = collect($structure)->contains(fn ($part) => ($part['type'] ?? '') === 'separator');
        $parts = collect($structure)
            ->sortBy(fn ($p) => $p['order'] ?? 0)
            ->map(function ($part) {
                $type = $part['type'] ?? '';
                $length = (int) ($part['length'] ?? 3);

                // Format nouveau (token/separator)
                if ($type === 'separator') {
                    return $part['value'] ?? '-';
                }
                if ($type === 'token') {
                    $token = $part['token'] ?? '';
                    if (in_array($token, ['SEQUENCE', 'NUMERO', 'SEQ'])) {
                        return str_pad('1', max(1, $length), '0', STR_PAD_LEFT);
                    }
                    return !empty($part['value']) ? $part['value'] : strtoupper($token);
                }

                // Format ancien
                if ($type === 'sequential_number') {
                    return str_pad('1', max(1, $length), '0', STR_PAD_LEFT);
                }

                if (!empty($part['value'])) {
                    return $part['value'];
                }

                return match($type) {
                    'document_type' => str_pad('XXX', max(1, $length), 'X'),
                    'process_code' => 'RH',
                    'subprocess_code' => str_pad('A1', max(1, $length), 'X'),
                    'year' => date('Y'),
                    'month' => date('m'),
                    'day' => date('d'),
                    'site_code' => 'SIT',
                    'custom' => str_repeat('X', max(1, $length)),
                    default => str_repeat('?', max(1, $length)),
                };
            })
            ->toArray();

        return $hasExplicitSeparators ? implode('', $parts) : implode($separator, $parts);
    }

    /**
     * Créer ou mettre à jour un template
     */
    public function createOrUpdate(array $data, ?int $templateId = null): NomenclatureTemplate
    {
        // Valider la structure
        if (isset($data['format_structure'])) {
            $data['format_structure'] = $this->validateFormatStructure($data['format_structure']);
        }

        if (($data['status'] ?? null) === 'published') {
            $data['is_active'] = true;
        }
        if (($data['status'] ?? null) === 'archived') {
            $data['is_active'] = false;
        }

        if (($data['status'] ?? null) === 'published' && !$templateId && empty($data['document_type_catalog_id'])) {
            throw ValidationException::withMessages([
                'document_type_catalog_id' => ['Un template publié doit être lié au type documentaire configuré dans la modale.'],
            ]);
        }

        // Générer l'exemple de prévisualisation
        if (isset($data['format_structure']) && isset($data['separator'])) {
            $data['preview_example'] = $this->generatePreviewExample(
                $data['format_structure'],
                $data['separator']
            );
        }

        return DB::transaction(function () use ($data, $templateId) {
            if ($templateId) {
                $template = NomenclatureTemplate::findOrFail($templateId);

                $targetStatus = $data['status'] ?? $template->status;
                $targetCatalogId = $data['document_type_catalog_id'] ?? $template->document_type_catalog_id;
                if ($targetStatus === 'published' && empty($targetCatalogId)) {
                    throw ValidationException::withMessages([
                        'document_type_catalog_id' => ['Un template publié doit être lié au type documentaire configuré dans la modale.'],
                    ]);
                }
                
                // Si on publie un template, incrémenter la version
                if (isset($data['status']) && $data['status'] === 'published' && $template->status !== 'published') {
                    $data['version'] = $template->version + 1;
                    $data['published_at'] = now();
                    $data['published_by'] = auth()->id();
                }
                
                $template->update($data);
                $template = $template->fresh();

                $this->archiveOtherPublishedTemplates($template);

                if ($this->shouldSyncTechnicalConfiguration($template)) {
                    $this->syncTechnicalConfiguration($template);
                }

                $this->ensureCatalogActiveStatus($template);

                return $template;
            }

            // Si on crée un template publié, archiver les précédents publiés du même scope
            if (($data['status'] ?? '') === 'published') {
                $query = NomenclatureTemplate::where('status', 'published')
                    ->where('enterprise_id', $data['enterprise_id'] ?? null)
                    ->where('site_id', $data['site_id'] ?? null);

                if (!empty($data['document_type_catalog_id'])) {
                    $query->where('document_type_catalog_id', $data['document_type_catalog_id']);
                }
                if (!empty($data['process_catalog_id'])) {
                    $query->where('process_catalog_id', $data['process_catalog_id']);
                }

                $query->update(['status' => 'archived', 'is_active' => false]);

                $data['published_at'] = now();
                $data['published_by'] = auth()->id();
                $data['is_active'] = true;
            }

            $template = NomenclatureTemplate::create(array_merge($data, [
                'version' => $data['version'] ?? 1,
            ]));

            $this->archiveOtherPublishedTemplates($template);

            if ($this->shouldSyncTechnicalConfiguration($template)) {
                $this->syncTechnicalConfiguration($template);
            }

            $this->ensureCatalogActiveStatus($template);

            return $template;
        });
    }

    private function archiveOtherPublishedTemplates(NomenclatureTemplate $template): void
    {
        if ($template->status !== 'published' || !$template->is_active) {
            return;
        }

        NomenclatureTemplate::query()
            ->where('enterprise_id', (int) $template->enterprise_id)
            ->where('site_id', $template->site_id)
            ->where('document_type_catalog_id', $template->document_type_catalog_id)
            ->where('process_catalog_id', $template->process_catalog_id)
            ->where('status', 'published')
            ->where('is_active', true)
            ->where('id', '!=', $template->id)
            ->update([
                'status' => 'archived',
                'is_active' => false,
            ]);
    }

    public function resolveActiveTemplate(
        int $siteId,
        string $documentType,
        ?int $templateId = null
    ): ?NomenclatureTemplate {
        $site = Site::query()->findOrFail($siteId);
        $abbreviation = strtoupper(trim($documentType));

        $catalog = DocumentTypeCatalog::query()
            ->where('enterprise_id', (int) $site->enterprise_id)
            ->whereRaw('UPPER(abbreviation) = ?', [$abbreviation])
            ->where('is_active', true)
            ->where(function ($query) use ($siteId) {
                $query->where('site_id', $siteId)->orWhereNull('site_id');
            })
            ->orderByRaw('CASE WHEN site_id = ? THEN 0 ELSE 1 END', [$siteId])
            ->latest('id')
            ->first();

        if (!$catalog) {
            return null;
        }

        $query = NomenclatureTemplate::query()
            ->where('enterprise_id', (int) $site->enterprise_id)
            ->where('document_type_catalog_id', (int) $catalog->id)
            ->where('status', 'published')
            ->where('is_active', true)
            ->where(function ($query) use ($siteId) {
                $query->where('site_id', $siteId)->orWhereNull('site_id');
            });

        if ($templateId) {
            $query->where('id', $templateId);
        }

        return $query
            ->orderByRaw('CASE WHEN site_id = ? THEN 0 ELSE 1 END', [$siteId])
            ->orderByDesc('version')
            ->latest('id')
            ->first();
    }

    public function syncTechnicalConfiguration(NomenclatureTemplate $template): DocumentTypeConfiguration
    {
        $template = $template->fresh(['documentTypeCatalog', 'site']);
        $catalog = $template->documentTypeCatalog;

        if (!$catalog) {
            throw ValidationException::withMessages([
                'document_type_catalog_id' => ['Un template publié doit être lié à un type documentaire.'],
            ]);
        }

        $abbreviation = strtoupper(trim((string) $catalog->abbreviation));
        $separator = $template->separator ?: '-';

        $config = DocumentTypeConfiguration::withTrashed()
            ->firstOrNew([
                'enterprise_id' => (int) $template->enterprise_id,
                'abbreviation' => $abbreviation,
            ]);

        if ($config->exists && method_exists($config, 'trashed') && $config->trashed()) {
            $config->restore();
        }

        $config->fill([
            'site_id' => null,
            'name' => $catalog->name,
            'abbreviation' => $abbreviation,
            'abbreviation_length' => max(1, min(10, strlen($abbreviation))),
            'scope' => 'enterprise',
            'is_active' => true,
            'description' => $template->description ?: $catalog->description,
        ]);
        $config->save();

        $parts = $this->templatePartsToCodeStructureParts(
            (array) ($template->format_structure ?? []),
            $separator
        );

        if (empty($parts)) {
            throw ValidationException::withMessages([
                'format_structure' => ['Le template publié doit contenir au moins une partie génératrice.'],
            ]);
        }

        $config->structureParts()->delete();

        foreach ($parts as $part) {
            CodeStructurePart::query()->create([
                'document_type_configuration_id' => $config->id,
                ...$part,
            ]);
        }

        return $config->fresh(['structureParts']);
    }

    private function shouldSyncTechnicalConfiguration(NomenclatureTemplate $template): bool
    {
        return $template->status === 'published'
            && (bool) $template->is_active
            && !empty($template->document_type_catalog_id);
    }

    private function templatePartsToCodeStructureParts(array $structure, string $separator): array
    {
        $normalized = collect($structure)
            ->filter(fn ($part) => is_array($part))
            ->sortBy(fn ($part) => (int) ($part['order'] ?? 0))
            ->values();

        $parts = [];
        $pendingSeparator = null;

        foreach ($normalized as $part) {
            $type = (string) ($part['type'] ?? '');

            if ($type === 'separator') {
                if (!empty($parts)) {
                    $parts[array_key_last($parts)]['separator_after'] = (string) ($part['value'] ?? $separator);
                } else {
                    $pendingSeparator = (string) ($part['value'] ?? $separator);
                }
                continue;
            }

            $mappedType = match ($type) {
                'document_type' => 'fixed_abbreviation',
                'process_code' => 'process_abbreviation',
                'sequential_number' => 'sequence',
                'year', 'month', 'day', 'site_code' => $type,
                'custom', 'free_text' => 'free_text',
                default => null,
            };

            if (!$mappedType) {
                continue;
            }

            $parts[] = [
                'part_order' => count($parts) + 1,
                'part_name' => (string) ($part['label'] ?? $this->labelForTechnicalPart($mappedType)),
                'part_type' => $mappedType,
                'part_length' => (int) ($part['length'] ?? ($mappedType === 'sequence' ? 3 : 3)),
                'separator_after' => $pendingSeparator,
                'is_required' => true,
                'default_value' => in_array($type, ['custom', 'free_text'], true)
                    ? ($part['value'] ?? null)
                    : null,
                'sequence_scope' => $mappedType === 'sequence'
                    ? (string) ($part['sequence_scope'] ?? 'by_type_process')
                    : null,
            ];
            $pendingSeparator = null;
        }

        $lastIndex = array_key_last($parts);
        foreach ($parts as $index => &$part) {
            if ($index !== $lastIndex && empty($part['separator_after'])) {
                $part['separator_after'] = $separator;
            }
        }
        unset($part);

        if ($lastIndex !== null) {
            $parts[$lastIndex]['separator_after'] = null;
        }

        return $parts;
    }

    private function labelForTechnicalPart(string $type): string
    {
        return match ($type) {
            'fixed_abbreviation' => 'Type de document',
            'process_abbreviation' => 'Processus',
            'sequence' => 'Séquence',
            'year' => 'Année',
            'month' => 'Mois',
            'day' => 'Jour',
            'site_code' => 'Site',
            'free_text' => 'Texte libre',
            default => 'Partie du code',
        };
    }

    /**
     * Générer le prochain code disponible
     */
    public function generateNextCode(
        int $siteId,
        string $documentType,
        ?int $nomenclatureTemplateId = null,
        array $context = []
    ): string {
        // Récupérer le template actif
        $template = $this->getActiveTemplate($siteId, $documentType, $nomenclatureTemplateId);
        
        if (!$template) {
            throw new \Exception("Aucun template de nomenclature actif trouvé pour le type {$documentType}.");
        }

        $structure = $template->format_structure;
        $separator = $template->separator ?? '-';

        // Construire les parties du code
        $parts = collect($structure)
            ->sortBy('order')
            ->map(function ($part) use ($context, $siteId, $documentType, $template) {
                if ($part['type'] === 'sequential_number') {
                    return $this->getNextSequentialNumber($siteId, $documentType, $template->id, $part['length']);
                }

                // Utiliser la valeur du contexte si disponible
                $contextKey = $part['type'];
                if (isset($context[$contextKey])) {
                    return $this->formatPart($context[$contextKey], $part['length']);
                }

                // Utiliser la valeur par défaut du template
                if (!empty($part['value'])) {
                    return $this->formatPart($part['value'], $part['length']);
                }

                throw new \Exception("Valeur manquante pour la partie '{$part['label']}' du code.");
            })
            ->toArray();

        return implode($separator, $parts);
    }

    /**
     * Obtenir le template actif pour un site et type de document
     */
    private function getActiveTemplate(int $siteId, string $documentType, ?int $templateId = null): ?NomenclatureTemplate
    {
        return $this->resolveActiveTemplate($siteId, $documentType, $templateId);
    }

    /**
     * Obtenir le prochain numéro séquentiel
     */
    private function getNextSequentialNumber(int $siteId, string $documentType, int $templateId, int $length): string
    {
        return DB::transaction(function () use ($siteId, $documentType, $templateId, $length) {
            // Verrouiller le template pour éviter les collisions entre utilisateurs simultanés
            NomenclatureTemplate::lockForUpdate()->find($templateId);

            // Vérifier s'il y a un code disponible dans le pool
            $availableCode = DocumentCodePool::forSiteAndType($siteId, $documentType)
                ->available()
                ->orderBy('code')
                ->lockForUpdate()
                ->first();

            if ($availableCode) {
                // Extraire le numéro séquentiel du code disponible
                $parts = explode('-', $availableCode->code);
                return end($parts);
            }

            // Sinon, générer un nouveau numéro
            $lastCode = DocumentCodePool::forSiteAndType($siteId, $documentType)
                ->orderBy('code', 'desc')
                ->first();

            if ($lastCode) {
                $parts = explode('-', $lastCode->code);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Formater une partie du code selon sa longueur
     */
    private function formatPart(string $value, int $length): string
    {
        $value = strtoupper(trim($value));
        
        if (strlen($value) > $length) {
            return substr($value, 0, $length);
        }
        
        if (strlen($value) < $length) {
            // Si c'est numérique, padder avec des zéros à gauche
            if (is_numeric($value)) {
                return str_pad($value, $length, '0', STR_PAD_LEFT);
            }
            // Sinon, padder avec des espaces à droite (ou laisser tel quel)
            return $value;
        }
        
        return $value;
    }

    /**
     * S'assure que le type documentaire est désactivé s'il n'y a plus aucun template actif publié pour ce site.
     */
    private function ensureCatalogActiveStatus(NomenclatureTemplate $template): void
    {
        $catalog = $template->documentTypeCatalog;
        if (!$catalog) {
            return;
        }

        $activeTemplatesCount = NomenclatureTemplate::query()
            ->where('document_type_catalog_id', $catalog->id)
            ->where('site_id', $template->site_id)
            ->where('status', 'published')
            ->where('is_active', true)
            ->count();

        if ($activeTemplatesCount === 0 && $catalog->is_active) {
            $catalog->update(['is_active' => false]);
        }
    }
}
