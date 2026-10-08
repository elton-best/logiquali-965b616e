<?php

namespace App\Http\Resources\Base;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class JsonApiResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->getResourceType(),
            'attributes' => $this->getAttributes(),
            'relationships' => $this->when($this->shouldIncludeRelationships(), $this->getRelationships()),
        ];
    }

    protected function getResourceType(): string
    {
        $class = class_basename($this->resource);
        return Str::kebab(Str::plural($class));
    }

    protected function getAttributes(): array
    {
        return [];
    }

    protected function getRelationships(): array
    {
        return [];
    }

    protected function shouldIncludeRelationships(): bool
    {
        return true; // Always include relationships if loaded
    }

    public function with($request)
    {
        return [
            'jsonapi' => [
                'version' => '1.0',
            ],
        ];
    }
}
