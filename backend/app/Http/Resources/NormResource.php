<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class NormResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}

