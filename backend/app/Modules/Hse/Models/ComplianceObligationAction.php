<?php

namespace App\Modules\Hse\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceObligationAction extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'text_id',
        'title',
        'responsible_id',
        'due_date',
        'status',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function text(): BelongsTo
    {
        return $this->belongsTo(ComplianceObligationText::class, 'text_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}

