<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'consent_type',
        'consent_given',
        'consent_date',
        'expiry_date',
        'consent_text',
        'consent_version',
        'metadata',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_date' => 'datetime',
        'expiry_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si le consentement est encore valide
     */
    public function isValid(): bool
    {
        if (!$this->consent_given) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope pour filtrer les consentements valides
     */
    public function scopeValid($query)
    {
        return $query->where('consent_given', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope pour filtrer par type de consentement
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }
}
