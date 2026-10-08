<?php

namespace App\Events\Enterprise;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnterpriseRegistered
{
    use Dispatchable, SerializesModels;

    public Enterprise $enterprise;
    public User $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(Enterprise $enterprise, User $admin)
    {
        $this->enterprise = $enterprise;
        $this->admin = $admin;
    }
}
