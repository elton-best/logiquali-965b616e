<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'enterprise_id',
        'user_id',
        'type',
        'name',
        'description',
        'parameters',
        'format',
        'status',
        'file_path',
        'generated_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'generated_at' => 'datetime',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
