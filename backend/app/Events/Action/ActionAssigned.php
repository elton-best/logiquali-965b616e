<?php

namespace App\Events\Action;

use App\Models\Action;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionAssigned
{
    use Dispatchable, SerializesModels;

    public Action $action;

    /**
     * Create a new event instance.
     */
    public function __construct(Action $action)
    {
        $this->action = $action;
    }
}
