<?php

namespace App\Modules\Evaluation\Models;

use App\Models\Site;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatisfactionSurvey extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'type',
        'year',
        'period',
        'respondent_name',
        'respondent_email',
        'responses',
        'total_score',
        'satisfaction_level',
        'recommendations',
        'm13_d3_traceability',
        'm13_d4_traceability',
        'm13_d6_traceability',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'responses' => 'array',
            'total_score' => 'decimal:2',
            'm13_d3_traceability' => 'array',
            'm13_d4_traceability' => 'array',
            'm13_d6_traceability' => 'array',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
