<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class PermissionResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'module' => $this->module,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}

