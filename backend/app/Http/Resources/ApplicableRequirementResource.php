<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ApplicableRequirementResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'type' => $this->type,
            'source' => $this->source,
            'description' => $this->description,
            'compliance_status' => $this->compliance_status,
            'actions' => $this->actions,
            'deadline' => $this->deadline?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
        ];
    }
}
