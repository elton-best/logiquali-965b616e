<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ActivityResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'input' => $this->input,
            'output' => $this->output,
            'order' => $this->order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'process' => new ProcessResource($this->whenLoaded('process')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
        ];
    }
}

