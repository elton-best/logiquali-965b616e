<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class PlanResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'title' => $this->title,
            'year' => $this->year,
            'content' => $this->content,
            'file_path' => $this->file_path,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
        ];
    }
}

