<?php

namespace App\Modules\Hse\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceObligationText extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'aspect_id',
        'ref',
        'regulatory_reference',
        'description',
        'applicable_requirement',
        'watch_source',
        'entry_into_force_date',
        'regulatory_change_status',
        'validity_status',
        'compliance_status',
        'actions_corrective_preventive',
        'deadline',
        'responsible_id',
        'evaluation_frequency',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'entry_into_force_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(ComplianceObligationAspect::class, 'aspect_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ComplianceObligationAction::class, 'text_id');
    }
}

