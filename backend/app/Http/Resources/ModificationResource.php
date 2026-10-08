<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ModificationResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'number' => $this->number,
            'date' => $this->date?->toISOString(),
            'object' => $this->object,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'consequences' => $this->consequences,
            'required_resources' => $this->required_resources,
            'validated_at' => $this->validated_at?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
            'validator' => new UserResource($this->whenLoaded('validator')),
        ];
    }
}
