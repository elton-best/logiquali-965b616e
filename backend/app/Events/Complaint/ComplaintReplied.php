<?php

namespace App\Events\Complaint;

use App\Models\Reclamation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintReplied
{
    use Dispatchable, SerializesModels;

    public Reclamation $complaint;
    public string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Reclamation $complaint, string $message)
    {
        $this->complaint = $complaint;
        $this->message = $message;
    }
}
