<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormationHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'formation_history';

    protected $fillable = [
        'formation_id',
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

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
