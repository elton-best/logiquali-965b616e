<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use App\Models\User;
use Illuminate\Http\Request;

class SiteResource extends JsonApiResource
{
    private function resolveEffectiveManager(): ?User
    {
        if ($this->manager) {
            return $this->manager;
        }

        if (!$this->is_headquarter || !$this->enterprise_id) {
            return null;
        }

        return User::query()
            ->where('enterprise_id', $this->enterprise_id)
            ->where('user_type', 'company')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('role', 'admin_entreprise')
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'admin_entreprise');
                    });
            })
            ->orderBy('id')
            ->first();
    }

    protected function getAttributes(): array
    {
        $effectiveManager = $this->resolveEffectiveManager();
        $activeNorms = collect();

        if ($this->relationLoaded('subscriptions')) {
            $activeNorms = collect($this->subscriptions)
                ->filter(fn($sub) => (bool) ($sub->is_active ?? false))
                ->flatMap(function ($sub) {
                    $norms = $sub->offer?->norms ?? collect();

                    if (collect($norms)->isEmpty() && $sub->offer?->name) {
                        return [$sub->offer->name];
                    }

                    return collect($norms)
                        ->map(fn($norm) => $norm->code ?: $norm->name)
                        ->filter();
                })
                ->unique()
                ->values();
        }

        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'location' => $this->location,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_headquarter' => $this->is_headquarter,
            'is_active' => $this->is_active,
            'manager_id' => $this->manager_id ?: $effectiveManager?->id,
            'manager_name' => $effectiveManager?->name,
            'active_norms' => $this->when($activeNorms->isNotEmpty(), $activeNorms),
            'processes_count' => $this->when(isset($this->processes_count), $this->processes_count),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        $effectiveManager = $this->resolveEffectiveManager();

        return [
            'enterprise' => $this->when(
                $this->relationLoaded('enterprise') && $this->enterprise,
                new EnterpriseResource($this->enterprise),
            ),
            'manager' => $this->when($effectiveManager !== null, new UserResource($effectiveManager)),
            'processes' => $this->whenLoaded('processes', fn () => ProcessResource::collection($this->processes)),
            'users' => $this->whenLoaded('users', fn () => UserResource::collection($this->users)),
            'subscriptions' => $this->whenLoaded('subscriptions', fn () => EnterpriseSubscriptionResource::collection($this->subscriptions)),
            'subscription' => $this->when(
                $this->relationLoaded('subscription') && $this->subscription,
                new EnterpriseSubscriptionResource($this->subscription),
            ),
        ];
    }
}
