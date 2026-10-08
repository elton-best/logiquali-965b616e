<?php

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserCreated
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $userType;
    public string $accessMode;
    public ?string $temporaryPassword;
    public ?string $setPasswordUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $user,
        string $userType,
        string $accessMode = 'set_password_link',
        ?string $temporaryPassword = null,
        ?string $setPasswordUrl = null
    ) {
        $this->user = $user;
        $this->userType = $userType; // 'collaborator' | 'client'
        $this->accessMode = $accessMode; // 'temporary_password' | 'set_password_link'
        $this->temporaryPassword = $temporaryPassword;
        $this->setPasswordUrl = $setPasswordUrl;
    }
}
