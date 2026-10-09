<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ManagementReviewResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'site_id' => $this->site_id,
            'title' => $this->title,
            'scheduled_date' => $this->scheduled_date?->toISOString(),
            'planned_date' => $this->planned_date?->toISOString(),
            'actual_date' => $this->actual_date?->toISOString(),
            'year' => $this->year,
            'quarter' => $this->quarter,
            'chairman_id' => $this->chairman_id,
            'participants' => $this->participants,
            'previous_actions_status' => $this->previous_actions_status,
            'context_changes' => $this->context_changes,
            'performance_indicators' => $this->performance_indicators,
            'customer_satisfaction' => $this->customer_satisfaction,
            'audit_results' => $this->audit_results,
            'nc_complaints_status' => $this->nc_complaints_status,
            'resources_adequacy' => $this->resources_adequacy,
            'improvement_opportunities' => $this->improvement_opportunities,
            'kpi_data' => $this->kpi_data,
            'objectives_data' => $this->objectives_data,
            'actions_data' => $this->actions_data,
            'risks_data' => $this->risks_data,
            'nc_data' => $this->nc_data,
            'audit_data' => $this->audit_data,
            'm12_d2_traceability' => $this->m12_d2_traceability,
            'm12_d3_traceability' => $this->m12_d3_traceability,
            'decisions' => $this->decisions,
            'action_items' => $this->action_items,
            'input_data' => $this->input_data,
            'output_decisions' => $this->output_decisions,
            'resources_data' => $this->resources_data,
            'system_changes_data' => $this->system_changes_data,
            'opened_at' => $this->opened_at?->toISOString(),
            'opened_by_user_id' => $this->opened_by_user_id,
            'closed_at' => $this->closed_at?->toISOString(),
            'closed_by_user_id' => $this->closed_by_user_id,
            'report_path' => $this->report_path,
            'generated_at' => $this->generated_at?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'chairman' => new UserResource($this->whenLoaded('chairman')),
            'openedBy' => new UserResource($this->whenLoaded('openedBy')),
            'closedBy' => new UserResource($this->whenLoaded('closedBy')),
        ];
    }
}
