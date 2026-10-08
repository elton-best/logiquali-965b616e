<?php

namespace App\Events\Enterprise;

use App\Models\Enterprise;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnterpriseApproved
{
    use Dispatchable, SerializesModels;

    public Enterprise $enterprise;

    /**
     * Create a new event instance.
     */
    public function __construct(Enterprise $enterprise)
    {
        $this->enterprise = $enterprise;
    }
}
