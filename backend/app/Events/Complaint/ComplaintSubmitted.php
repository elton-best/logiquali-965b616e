<?php

namespace App\Events\Complaint;

use App\Models\Reclamation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintSubmitted
{
    use Dispatchable, SerializesModels;

    public Reclamation $complaint;

    /**
     * Create a new event instance.
     */
    public function __construct(Reclamation $complaint)
    {
        $this->complaint = $complaint;
    }
}
