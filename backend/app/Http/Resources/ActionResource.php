<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ActionResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'source' => $this->source,
            'root_cause' => $this->root_cause,
            'immediate_action' => $this->immediate_action,
            'immediate_action_date' => $this->immediate_action_date?->toISOString(),
            'deadline' => $this->deadline?->toISOString(),
            'status' => $this->status,
            'effectiveness_verified' => $this->effectiveness_verified,
            'verification_date' => $this->verification_date?->toISOString(),
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
            'initiator' => new UserResource($this->whenLoaded('initiator')),
        ];
    }
}

