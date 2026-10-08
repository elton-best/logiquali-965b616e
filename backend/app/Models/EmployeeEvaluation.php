<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEvaluation extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'user_id',
        'evaluator_id',
        'period',
        'year',
        'scores',
        'total_score',
        'comments',
        'm13_d3_traceability',
        'm13_d6_traceability',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'scores' => 'array',
            'total_score' => 'decimal:2',
            'm13_d3_traceability' => 'array',
            'm13_d6_traceability' => 'array',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
