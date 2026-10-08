<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class DashboardResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'year' => $this->year,
            'period' => $this->period,
            'indicators' => $this->indicators,
            'actions' => $this->actions,
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

