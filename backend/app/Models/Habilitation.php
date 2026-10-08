<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habilitation extends Model
{
    use BelongsToEnterprise, SoftDeletes, HasFactory;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'user_id',
        'type',
        'title',
        'description',
        'certificate_number',
        'issued_date',
        'expiry_date',
        'issuing_authority',
        'status',
        'certificate_path',
        'notes'
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExpiresSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
                    ->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date <= now()->addDays($days) && !$this->isExpired();
    }

    public function getDaysUntilExpiry(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }
}