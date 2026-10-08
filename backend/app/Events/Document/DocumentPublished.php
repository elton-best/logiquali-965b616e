<?php

namespace App\Events\Document;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DocumentPublished
{
    use Dispatchable, SerializesModels;

    public Document $document;
    public Collection $concernedUsers;
    public User $publishedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document, Collection $concernedUsers, User $publishedBy)
    {
        $this->document = $document;
        $this->concernedUsers = $concernedUsers;
        $this->publishedBy = $publishedBy;
    }
}
