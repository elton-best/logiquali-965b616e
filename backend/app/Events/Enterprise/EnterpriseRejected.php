<?php

namespace App\Events\Enterprise;

use App\Models\Enterprise;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnterpriseRejected
{
    use Dispatchable, SerializesModels;

    public Enterprise $enterprise;
    public ?string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Enterprise $enterprise, ?string $reason = null)
    {
        $this->enterprise = $enterprise;
        $this->reason = $reason;
    }
}
