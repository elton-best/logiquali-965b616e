<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCodeRelease extends Model
{
    protected $fillable = [
        'entreprise_id',
        'code',
        'original_document_id',
        'release_reason',
        'released_by',
        'released_at',
        'reused_by_document_id',
        'reused_at',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'reused_at' => 'datetime',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'entreprise_id');
    }

    public function originalDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'original_document_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function reusedByDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'reused_by_document_id');
    }

    public function scopeAvailable($query, int $entrepriseId)
    {
        return $query->where('entreprise_id', $entrepriseId)
                     ->whereNull('reused_at');
    }

    public function scopeForCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
