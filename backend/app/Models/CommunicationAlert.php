<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'communication_id',
        'type',
        'date',
        'sent',
        'sent_at',
    ];

    protected $casts = [
        'date' => 'date',
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class);
    }
}
