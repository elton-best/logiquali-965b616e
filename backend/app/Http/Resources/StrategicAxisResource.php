<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class StrategicAxisResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'objectives' => ObjectiveResource::collection($this->whenLoaded('objectives')),
        ];
    }
}

