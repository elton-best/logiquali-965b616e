<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class OfferResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'price' => $this->price,
            'duration_months' => $this->duration_months,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'norms' => NormResource::collection($this->whenLoaded('norms')),
            'subscriptions' => EnterpriseSubscriptionResource::collection($this->whenLoaded('subscriptions')),
        ];
    }
}

