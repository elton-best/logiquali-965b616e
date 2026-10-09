<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class EnterpriseResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'sigle' => $this->sigle,
            'codification_mode' => $this->codification_mode,
            'email' => $this->email,
            'owner_user_id' => $this->owner_user_id,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'registration_number' => $this->registration_number,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'suspension_reason' => $this->suspension_reason,
            'organigram_path' => $this->organigram_path,
            'field' => $this->field,
            'domaine_activite_set' => $this->domaine_activite_set,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'sites' => $this->whenLoaded('sites', fn () => SiteResource::collection($this->sites)),
            'users' => $this->whenLoaded('users', fn () => UserResource::collection($this->users)),
        ];
    }
}
