<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EnterpriseCertification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'certification_id',
        'certificate_number',
        'certifying_body',
        'issue_date',
        'expiry_date',
        'logo_path',
        'status',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    /**
     * Entreprise propriétaire
     */
    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    /**
     * Certification du catalogue
     */
    public function certification()
    {
        return $this->belongsTo(CertificationCatalog::class, 'certification_id');
    }

    /**
     * Scope: Certifications actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope: Certifications expirant bientôt
     */
    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Certifications expirées
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Vérifier si la certification est expirée
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Nombre de jours avant expiration
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * Mettre à jour le statut automatiquement
     */
    public function updateStatus(): void
    {
        if ($this->isExpired()) {
            $this->update(['status' => 'expired']);
        } elseif ($this->daysUntilExpiry() !== null && $this->daysUntilExpiry() <= 90) {
            $this->update(['status' => 'pending_renewal']);
        }
    }
}
