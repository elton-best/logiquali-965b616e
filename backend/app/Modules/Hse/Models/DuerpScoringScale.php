<?php

namespace App\Modules\Hse\Models;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuerpScoringScale extends Model
{
    use HasFactory;

    protected $table = 'duerp_scoring_scales';

    protected $fillable = [
        'enterprise_id',
        'scale_type', // 'gravity', 'frequency', 'maitrise'
        'level',
        'label',
        'score_value',
        'cadence',
        'consequences',
        'description',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'score_value' => 'float',
        'is_active' => 'boolean',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}

