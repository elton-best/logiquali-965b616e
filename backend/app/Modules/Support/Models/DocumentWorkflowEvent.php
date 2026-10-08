<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'actor_user_id',
        'chain_index',
        'event_type',
        'from_status',
        'to_status',
        'comment',
        'metadata',
        'previous_event_hash',
        'event_hash',
        'event_signature',
        'occurred_at',
        'sealed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'sealed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

