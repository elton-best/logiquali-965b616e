<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class OpportunityResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'description' => $this->description,
            'exploitation_action' => $this->exploitation_action,
            'deadline' => $this->deadline?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'process' => new ProcessResource($this->whenLoaded('process')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
        ];
    }
}

