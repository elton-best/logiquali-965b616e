<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeStructurePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_configuration_id',
        'part_order',
        'part_name',
        'part_type',
        'part_length',
        'separator_after',
        'is_required',
        'default_value',
        'sequence_scope',
    ];

    protected $casts = [
        'part_order' => 'integer',
        'part_length' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Relations
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(DocumentTypeConfiguration::class, 'document_type_configuration_id');
    }

    /**
     * Résout la valeur de cette partie selon son type et le contexte
     *
     * @param array $context ['process_id' => 1, 'year' => 2026, 'month' => 4, 'site_id' => 1]
     * @return string|null
     */
    public function resolveValue(array $context = []): ?string
    {
        $value = match ($this->part_type) {
            'fixed_abbreviation' => $this->configuration->abbreviation,
            'process_abbreviation' => $this->resolveProcessAbbreviation($context),
            'sequence' => $this->resolveSequencePlaceholder(),
            'year' => $context['year'] ?? date('Y'),
            'month' => str_pad((string) ($context['month'] ?? date('m')), 2, '0', STR_PAD_LEFT),
            'day' => str_pad((string) ($context['day'] ?? date('d')), 2, '0', STR_PAD_LEFT),
            'site_code' => $this->resolveSiteCode($context),
            'free_text' => $context['free_text'][$this->part_order] ?? $this->default_value,
            default => $this->default_value,
        };

        return $this->formatValue($value);
    }

    /**
     * Résout l'abréviation du processus
     *
     * @param array $context
     * @return string|null
     */
    private function resolveProcessAbbreviation(array $context): ?string
    {
        if (!isset($context['process_id'])) {
            return null;
        }

        $process = \App\Models\Process::find($context['process_id']);
        if (!$process) {
            return null;
        }

        if (!empty($process->abbreviation)) {
            return strtoupper($process->abbreviation);
        }

        // Fallback: trigramme à partir du titre
        $title = $process->title ?? $process->name ?? '';
        if (empty(trim($title))) {
            return 'PRC';
        }

        $cleanTitle = preg_replace('/[^A-Za-z0-9\s]/', '', \Illuminate\Support\Str::ascii($title));
        $words = array_values(array_filter(explode(' ', strtoupper($cleanTitle))));

        if (count($words) >= 3) {
            return substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
        } elseif (count($words) === 2) {
            return substr($words[0], 0, 2) . substr($words[1], 0, 1);
        } else {
            return substr(str_replace(' ', '', strtoupper($cleanTitle)), 0, 3);
        }
    }

    /**
     * Résout le code du site
     *
     * @param array $context
     * @return string|null
     */
    private function resolveSiteCode(array $context): ?string
    {
        if (!isset($context['site_id'])) {
            return null;
        }

        $site = Site::find($context['site_id']);
        return $site?->code ?? null;
    }

    /**
     * Pour les séquences, retourne un placeholder qui sera remplacé plus tard
     *
     * @return string
     */
    private function resolveSequencePlaceholder(): string
    {
        return str_repeat('0', $this->part_length ?? 3);
    }

    /**
     * Formate la valeur selon la longueur définie
     *
     * @param string|null $value
     * @return string|null
     */
    public function formatValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Pour les séquences et numéros, padding avec des zéros
        if (in_array($this->part_type, ['sequence', 'month', 'day']) && $this->part_length) {
            return str_pad($value, $this->part_length, '0', STR_PAD_LEFT);
        }

        // Pour les autres types, tronquer si nécessaire
        if ($this->part_length && strlen($value) > $this->part_length) {
            return substr($value, 0, $this->part_length);
        }

        return strtoupper($value);
    }

    /**
     * Vérifie si cette partie est une séquence
     *
     * @return bool
     */
    public function isSequence(): bool
    {
        return $this->part_type === 'sequence';
    }

    /**
     * Récupère les paramètres de scope pour la séquence
     *
     * @param array $context
     * @return array
     */
    public function getSequenceScopeParams(array $context): array
    {
        if (!$this->isSequence()) {
            return [];
        }

        $params = [
            'document_type_configuration_id' => $this->document_type_configuration_id,
        ];

        // Ajouter les paramètres selon le scope
        if (str_contains($this->sequence_scope, 'process') && isset($context['process_id'])) {
            $params['process_id'] = $context['process_id'];
        }

        if (str_contains($this->sequence_scope, 'year') && isset($context['year'])) {
            $params['year'] = $context['year'];
        }

        if (str_contains($this->sequence_scope, 'month') && isset($context['month'])) {
            $params['month'] = $context['month'];
        }

        return $params;
    }
}
