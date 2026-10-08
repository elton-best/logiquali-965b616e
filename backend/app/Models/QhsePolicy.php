<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class QhsePolicy extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'version',
        'is_current',
        'effective_date',
        'mission',
        'vision',
        'values',
        'axes',
        'commitments',
        'quality_policy',
        'environmental_policy',
        'health_safety_policy',
        'status',
        'validated_by_direction_at',
        'validated_by_user_id',
        'document_path',
        'generated_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'effective_date' => 'date',
        'values' => 'array',
        'axes' => 'array',
        'commitments' => 'array',
        'validated_by_direction_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function axisEntries()
    {
        return $this->hasMany(QhsePolicyAxis::class)->orderBy('axis_order');
    }

    public function syncAxes(array $axes): void
    {
        if (!Schema::hasTable('qhse_policy_axes')) {
            return;
        }

        $normalized = array_values(array_filter(
            array_map(fn ($axis) => trim((string) $axis), $axes),
            fn ($axis) => $axis !== ''
        ));

        $this->axisEntries()->delete();

        foreach ($normalized as $index => $axisName) {
            $this->axisEntries()->create([
                'axis_name' => $axisName,
                'axis_order' => $index + 1,
            ]);
        }
    }
}
