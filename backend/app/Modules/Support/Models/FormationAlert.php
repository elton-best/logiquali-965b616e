<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormationAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'formation_id',
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

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
}
