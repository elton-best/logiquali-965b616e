<?php

namespace App\Events\Action;

use App\Models\Action;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionOverdue
{
    use Dispatchable, SerializesModels;

    public Action $action;
    public int $daysOverdue;

    /**
     * Create a new event instance.
     */
    public function __construct(Action $action, int $daysOverdue)
    {
        $this->action = $action;
        $this->daysOverdue = $daysOverdue;
    }
}
