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
        'resources_data',
        'system_changes_data',
        'opened_at',
        'opened_by_user_id',
        'closed_at',
        'closed_by_user_id',
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
            'resources_data' => 'array',
            'system_changes_data' => 'array',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function getResourcesDataAttribute($value)
    {
        if (!empty($value)) {
            return is_string($value) ? json_decode($value, true) : $value;
        }

        $inputData = $this->input_data;
        if (is_array($inputData) && !empty($inputData['resources_data'])) {
            return $inputData['resources_data'];
        }

        if (!empty($this->resources_adequacy)) {
            return [
                'synthese_observations' => $this->resources_adequacy,
                'decision_action' => null,
                'responsable' => null,
                'delai' => null,
            ];
        }

        return [
            'synthese_observations' => null,
            'decision_action' => null,
            'responsable' => null,
            'delai' => null,
        ];
    }

    public function getSystemChangesDataAttribute($value)
    {
        if (!empty($value)) {
            return is_string($value) ? json_decode($value, true) : $value;
        }

        $outputDecisions = $this->output_decisions;
        if (is_array($outputDecisions) && !empty($outputDecisions['system_changes_data'])) {
            return $outputDecisions['system_changes_data'];
        }

        return [
            'besoins_changements_systeme' => [],
            'autres_besoins_changements_systeme' => [],
        ];
    }
}
