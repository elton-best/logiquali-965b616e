<?php

namespace App\Modules\Improvement\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTracking extends Model
{
    use SoftDeletes;

    protected $table = 'task_tracking';

    protected $fillable = [
        'user_id',
        'trackable_type',
        'trackable_id',
        'status',
        'progress_rate',
        'notes',
        'tracked_at',
    ];

    protected $casts = [
        'status' => 'string',
        'progress_rate' => 'integer',
        'tracked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskTrackingHistory::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTrackableType($query, $type)
    {
        return $query->where('trackable_type', $type);
    }

    // Validation métier
    public function validate(): array
    {
        $errors = [];

        if ($this->status === 'non_demarre' && $this->progress_rate !== 0) {
            $errors[] = 'Pour un statut "non démarré", le taux doit être 0';
        }

        if ($this->status === 'termine' && $this->progress_rate !== 100) {
            $errors[] = 'Pour un statut "terminé", le taux doit être 100';
        }

        if ($this->status === 'en_cours') {
            if ($this->progress_rate === null || $this->progress_rate < 1 || $this->progress_rate > 99) {
                $errors[] = 'Pour un statut "en cours", le taux doit être entre 1 et 99';
            }
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}

