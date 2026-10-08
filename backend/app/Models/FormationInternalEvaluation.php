<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormationInternalEvaluation extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'formation_id',
        'evaluator_id',
        'method',
        'scores',
        'global_score',
        'strengths',
        'improvements',
        'comment',
        'evaluated_at',
    ];

    protected $casts = [
        'scores' => 'array',
        'strengths' => 'array',
        'improvements' => 'array',
        'global_score' => 'decimal:2',
        'evaluated_at' => 'date',
    ];

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
