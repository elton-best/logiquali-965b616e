<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminSetting extends Model
{
    protected $fillable = [
        'general',
        'email',
        'security',
        'system',
        'updated_by',
    ];

    protected $casts = [
        'general' => 'array',
        'email' => 'array',
        'security' => 'array',
        'system' => 'array',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
