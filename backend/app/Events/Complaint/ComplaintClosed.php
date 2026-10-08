<?php

namespace App\Events\Complaint;

use App\Models\Reclamation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintClosed
{
    use Dispatchable, SerializesModels;

    public Reclamation $complaint;
    public ?string $surveyUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(Reclamation $complaint, ?string $surveyUrl = null)
    {
        $this->complaint = $complaint;
        $this->surveyUrl = $surveyUrl;
    }
}
