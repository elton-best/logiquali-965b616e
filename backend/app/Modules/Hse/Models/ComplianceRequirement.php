<?php

namespace App\Modules\Hse\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRequirement extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'requirement_type',
        'title',
        'reference',
        'description',
        'source',
        'source_url',
        'publication_date',
        'effective_date',
        'applicable_to',
        'applicable_norms',
        'compliance_status',
        'last_evaluation_date',
        'next_evaluation_date',
        'action_ids',
        'responsible_user_id',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'effective_date' => 'date',
        'last_evaluation_date' => 'date',
        'next_evaluation_date' => 'date',
        'applicable_to' => 'array',
        'applicable_norms' => 'array',
        'action_ids' => 'array',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
