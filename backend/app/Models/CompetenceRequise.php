<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetenceRequise extends Model
{
    use BelongsToEnterprise, HasFactory;

    protected $table = 'competences_requises';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'job_description_id',
        'competence_type',
        'competence_name',
        'description',
        'level_required',
        'priority',
        'validity_months',
        'requires_certification',
        'certification_authority'
    ];

    protected $casts = [
        'requires_certification' => 'boolean'
    ];

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function competencesAcquises(): HasMany
    {
        return $this->hasMany(CompetenceAcquise::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('competence_type', $type);
    }

    public function scopeObligatoire($query)
    {
        return $query->where('priority', 'obligatoire');
    }

    public function scopeWithCertification($query)
    {
        return $query->where('requires_certification', true);
    }

    public function isObligatoire(): bool
    {
        return $this->priority === 'obligatoire';
    }

    public function hasExpiry(): bool
    {
        return !is_null($this->validity_months);
    }
}