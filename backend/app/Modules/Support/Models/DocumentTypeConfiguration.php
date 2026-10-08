<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTypeConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'name',
        'abbreviation',
        'abbreviation_length',
        'scope',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'abbreviation_length' => 'integer',
    ];

    /**
     * Relations
     */
    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function structureParts(): HasMany
    {
        return $this->hasMany(CodeStructurePart::class)->orderBy('part_order');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(CodeSequence::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSite(\Illuminate\Database\Eloquent\Builder $query, int $siteId)
    {
        return $query->where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)
              ->orWhere('scope', 'enterprise');
        });
    }

    public function scopeForEnterprise(\Illuminate\Database\Eloquent\Builder $query, int $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    /**
     * Génère un aperçu du code selon la configuration
     *
     * @param array $context ['process_id' => 1, 'year' => 2026, 'month' => 4]
     * @return string
     */
    public function generatePreviewCode(array $context = []): string
    {
        $parts = [];
        
        foreach ($this->structureParts as $part) {
            $value = $part->resolveValue($context);
            
            if ($value !== null) {
                $parts[] = $value;
                
                if ($part->separator_after) {
                    $parts[] = $part->separator_after;
                }
            }
        }

        // Retirer le dernier séparateur si présent
        $code = implode('', $parts);
        return rtrim($code, '-_./');
    }

    /**
     * Valide que la structure est cohérente
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStructure(): array
    {
        $errors = [];
        $hasSequence = false;

        if ($this->structureParts->isEmpty()) {
            $errors[] = 'La structure doit contenir au moins une partie.';
        }

        foreach ($this->structureParts as $part) {
            if ($part->part_type === 'sequence') {
                $hasSequence = true;
                
                if (!$part->sequence_scope) {
                    $errors[] = "La partie séquence (ordre {$part->part_order}) doit avoir un scope défini.";
                }
            }

            if ($part->is_required && $part->part_type === 'free_text') {
                $errors[] = "La partie texte libre (ordre {$part->part_order}) ne peut pas être obligatoire.";
            }
        }

        if (!$hasSequence) {
            $errors[] = 'La structure doit contenir au moins une partie de type séquence.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Récupère la partie séquence de cette configuration
     *
     * @return CodeStructurePart|null
     */
    public function getSequencePart(): ?CodeStructurePart
    {
        return $this->structureParts->firstWhere('part_type', 'sequence');
    }
}
