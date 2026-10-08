<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class NonConformityResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'severity' => $this->severity,
            'description' => $this->description,
            'detected_by' => $this->detected_by,
            'detected_at' => $this->detected_at?->toISOString(),
            'root_cause_analysis' => $this->root_cause_analysis,
            'corrective_action' => $this->corrective_action,
            'preventive_action' => $this->preventive_action,
            'deadline' => $this->deadline?->toISOString(),
            'status' => $this->status,
            'resolution_date' => $this->resolution_date?->toISOString(),
            'effectiveness_verified' => $this->effectiveness_verified,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'process' => new ProcessResource($this->whenLoaded('process')),
            'audit' => new AuditResource($this->whenLoaded('audit')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
        ];
    }
}

