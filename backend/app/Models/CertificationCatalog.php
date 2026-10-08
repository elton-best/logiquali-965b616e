<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CertificationCatalog extends Model
{
    use HasFactory;

    protected $table = 'certifications_catalog';

    protected $fillable = [
        'type',
        'code',
        'name',
        'description',
        'logo_url',
        'issuing_body',
        'validity_years',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'validity_years' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Certifications d'entreprises utilisant ce catalogue
     */
    public function enterpriseCertifications()
    {
        return $this->hasMany(EnterpriseCertification::class, 'certification_id');
    }

    /**
     * Scope: Certifications actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: ISO uniquement
     */
    public function scopeIso($query)
    {
        return $query->where('type', 'ISO');
    }
}
