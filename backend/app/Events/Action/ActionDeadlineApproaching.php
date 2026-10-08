<?php

namespace App\Events\Action;

use App\Models\Action;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionDeadlineApproaching
{
    use Dispatchable, SerializesModels;

    public Action $action;
    public int $daysRemaining;

    /**
     * Create a new event instance.
     */
    public function __construct(Action $action, int $daysRemaining)
    {
        $this->action = $action;
        $this->daysRemaining = $daysRemaining;
    }
}
