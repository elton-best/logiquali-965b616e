<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    public $token;
    public $expiresAt;

    public function __construct($resource, $token = null, $expiresAt = null)
    {
        parent::__construct($resource);
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->id,
                'ref' => $this->ref,
                'username' => $this->username,
                'email' => $this->email,
                'phone' => $this->phone,
                'photo_path' => $this->photo_path,
                'photo_url' => $this->photo_url,
                'signature_path' => $this->signature_path,
                'signature_url' => $this->signature_url,
                'signature_uploaded_at' => $this->signature_uploaded_at?->toISOString(),
                'user_type' => $this->user_type,
                'is_active' => $this->is_active,
                'email_verified' => $this->hasVerifiedEmail(),
                'email_verified_at' => $this->email_verified_at,
                'last_login_at' => $this->last_login_at,
                'enterprise' => $this->whenLoaded('enterprise'),
                'site' => $this->whenLoaded('site'),
                'permissions' => $this->whenLoaded('permissions'),
            ],
            'role' => $this->user_type,
            'token' => $this->when($this->token, $this->token),
            'token_type' => $this->when($this->token, 'Bearer'),
            'expires_at' => $this->when($this->expiresAt, $this->expiresAt),
            'email_verified' => $this->hasVerifiedEmail(),
        ];
    }
}
