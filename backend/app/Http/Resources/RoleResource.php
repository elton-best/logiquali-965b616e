<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class RoleResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'description' => $this->description,
            'enterprise_id' => $this->enterprise_id ? (int) $this->enterprise_id : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
