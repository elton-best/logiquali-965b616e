<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ObjectiveResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'indicator' => $this->indicator,
            'target_value' => $this->target_value,
            'current_value' => $this->current_value,
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
            'strategic_axis' => new StrategicAxisResource($this->whenLoaded('strategicAxis')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
        ];
    }
}

