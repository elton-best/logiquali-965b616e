<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ProcessInteractionResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'supplier_process' => new ProcessResource($this->whenLoaded('supplierProcess')),
            'self_process' => new ProcessResource($this->whenLoaded('selfProcess')),
            'client_process' => new ProcessResource($this->whenLoaded('clientProcess')),
        ];
    }
}

