<?php

namespace App\Modules\Evaluation\Models;

use App\Models\Site;
use App\Models\User;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManagementReview extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'title',
        'scheduled_date',
        'planned_date',
        'actual_date',
        'year',
        'quarter',
        'status',
        'chairman_id',
        'participants',
        'previous_actions_status',
        'context_changes',
        'performance_indicators',
        'customer_satisfaction',
        'audit_results',
        'nc_complaints_status',
        'resources_adequacy',
        'improvement_opportunities',
        'kpi_data',
        'objectives_data',
        'actions_data',
        'risks_data',
        'nc_data',
        'audit_data',
        'm12_d2_traceability',
        'm12_d3_traceability',
        'decisions',
        'action_items',
        'input_data',
        'output_decisions',
        'action_ids',
        'status_workflow',
        'validated_by_ceo_at',
        'validated_by_ceo_user_id',
        'report_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'actual_date' => 'date',
            'scheduled_date' => 'date',
            'participants' => 'array',
            'decisions' => 'array',
            'kpi_data' => 'array',
            'objectives_data' => 'array',
            'actions_data' => 'array',
            'risks_data' => 'array',
            'nc_data' => 'array',
            'audit_data' => 'array',
            'm12_d2_traceability' => 'array',
            'm12_d3_traceability' => 'array',
            'action_items' => 'array',
            'input_data' => 'array',
            'output_decisions' => 'array',
            'action_ids' => 'array',
            'validated_by_ceo_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    public function chairman()
    {
        return $this->belongsTo(User::class, 'chairman_id');
    }

    public function validatedByCeo()
    {
        return $this->belongsTo(User::class, 'validated_by_ceo_user_id');
    }
}
