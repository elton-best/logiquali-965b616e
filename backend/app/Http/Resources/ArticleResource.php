<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class ArticleResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'content' => $this->content,
            'order' => $this->order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'norm' => new NormResource($this->whenLoaded('norm')),
            'parent' => new ArticleResource($this->whenLoaded('parent')),
            'children' => ArticleResource::collection($this->whenLoaded('children')),
        ];
    }
}

