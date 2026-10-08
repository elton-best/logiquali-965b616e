<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class TeamMemberResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'process' => new ProcessResource($this->whenLoaded('process')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

