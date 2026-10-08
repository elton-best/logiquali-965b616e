<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo_path' => $this->photo_path,
            'user_type' => $this->user_type,
            'role' => $this->user_type,
            'is_active' => $this->is_active,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'enterprise' => $this->whenLoaded('enterprise'),
            'site' => $this->whenLoaded('site'),
            'permissions' => $this->whenLoaded('permissions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
