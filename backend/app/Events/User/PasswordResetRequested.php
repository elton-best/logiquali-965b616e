<?php

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PasswordResetRequested
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $token;
    public ?string $resetUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $token, ?string $resetUrl = null)
    {
        $this->user = $user;
        $this->token = $token;
        $this->resetUrl = $resetUrl;
    }
}
