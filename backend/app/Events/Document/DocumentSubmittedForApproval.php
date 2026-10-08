<?php

namespace App\Events\Document;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DocumentSubmittedForApproval
{
    use Dispatchable, SerializesModels;

    public Document $document;
    public Collection $approvers;
    public User $submittedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document, Collection $approvers, User $submittedBy)
    {
        $this->document = $document;
        $this->approvers = $approvers;
        $this->submittedBy = $submittedBy;
    }
}
