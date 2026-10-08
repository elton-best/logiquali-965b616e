<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ProcessResourceResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'process' => new ProcessResource($this->whenLoaded('process')),
        ];
    }
}

