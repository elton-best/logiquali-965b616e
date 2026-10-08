<?php

namespace App\Modules\Support\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetenceAcquise extends Model
{
    use BelongsToEnterprise, HasFactory;

    protected $table = 'competences_acquises';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'user_id',
        'competence_requise_id',
        'level_acquired',
        'acquired_date',
        'expiry_date',
        'acquisition_method',
        'formation_id',
        'habilitation_id',
        'proof_document',
        'notes',
        'status'
    ];

    protected $casts = [
        'acquired_date' => 'date',
        'expiry_date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competenceRequise(): BelongsTo
    {
        return $this->belongsTo(CompetenceRequise::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function habilitation(): BelongsTo
    {
        return $this->belongsTo(Habilitation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiresSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('status', 'active');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date <= now()->addDays($days) && !$this->isExpired();
    }

    public function meetsRequiredLevel(): bool
    {
        $levels = ['base' => 1, 'intermediaire' => 2, 'avance' => 3, 'expert' => 4];
        
        return $levels[$this->level_acquired] >= $levels[$this->competenceRequise->level_required];
    }

    public function getDaysUntilExpiry(): ?int
    {
        return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null;
    }
}