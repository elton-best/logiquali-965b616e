<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationProof extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'communication_id',
        'filename',
        'url',
        'uploaded_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
