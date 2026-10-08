<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class StakeholderResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'type' => $this->type,
            'relevance_degree' => $this->relevance_degree,
            'needs_expectations' => $this->needs_expectations,
            'requirements' => $this->requirements,
            'actions' => $this->actions,
            'contact_person' => $this->contact_person,
            'needs' => $this->needs,
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
