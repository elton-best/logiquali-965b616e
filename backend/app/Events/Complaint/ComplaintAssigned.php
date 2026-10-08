<?php

namespace App\Events\Complaint;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintAssigned
{
    use Dispatchable, SerializesModels;

    public Reclamation $complaint;
    public User $assignedTo;

    /**
     * Create a new event instance.
     */
    public function __construct(Reclamation $complaint, User $assignedTo)
    {
        $this->complaint = $complaint;
        $this->assignedTo = $assignedTo;
    }
}
