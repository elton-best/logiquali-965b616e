<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class EnterpriseSubscriptionResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'start_date' => $this->start_date?->toISOString(),
            'expiration_date' => $this->expiration_date?->toISOString(),
            'is_active' => $this->is_active,
            'is_trial' => $this->is_trial ?? false,
            'payment_method' => $this->payment_method,
            'amount_paid' => $this->amount_paid,
            'plan_name' => $this->when($this->relationLoaded('offer'), fn() => $this->offer->name),
            'price' => $this->when($this->relationLoaded('offer'), fn() => $this->offer->price),
            'billing_period' => $this->when($this->relationLoaded('offer'), fn() => $this->offer->billing_period ?? 'Mensuel', 'Mensuel'),
            'status' => $this->getStatus(),
            'auto_renew' => true,
            'site_id' => $this->site_id,
            'site_name' => $this->when($this->relationLoaded('site'), fn() => $this->site->name),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'offer' => new OfferResource($this->whenLoaded('offer')),
            'site' => new SiteResource($this->whenLoaded('site')),
        ];
    }
    
    private function getStatus(): string
    {
        if (!$this->is_active) {
            return 'cancelled';
        }
        
        if ($this->expiration_date && $this->expiration_date->isPast()) {
            return 'expired';
        }
        
        if ($this->is_trial) {
            return 'trial';
        }
        
        return 'active';
    }
}

