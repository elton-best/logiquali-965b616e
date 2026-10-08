<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'communication_id',
        'action',
        'user_id',
        'user_name',
        'comment',
        'previous_date',
        'new_date',
    ];

    protected $casts = [
        'previous_date' => 'date',
        'new_date' => 'date',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
