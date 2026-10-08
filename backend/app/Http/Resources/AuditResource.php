<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class AuditResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'title' => $this->title,
            'planned_date' => $this->planned_date?->toISOString(),
            'actual_date' => $this->actual_date?->toISOString(),
            'team_members' => $this->team_members,
            'scope' => $this->scope,
            'status' => $this->status,
            'global_report_path' => $this->global_report_path,
            'report_path' => $this->report_path,
            'external_report_path' => $this->external_report_path,
            'report_source' => $this->report_source,
            'report_version' => $this->report_version,
            'external_report_uploaded_at' => $this->external_report_uploaded_at?->toISOString(),
            'm9_d2_traceability' => $this->m9_d2_traceability,
            'm9_d5_traceability' => $this->m9_d5_traceability,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'lead_auditor' => new UserResource($this->whenLoaded('leadAuditor')),
        ];
    }
}
