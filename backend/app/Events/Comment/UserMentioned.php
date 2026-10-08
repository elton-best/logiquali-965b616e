<?php

namespace App\Events\Comment;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned
{
    use Dispatchable, SerializesModels;

    public User $mentionedUser;
    public User $author;
    public $comment; // Polymorphic comment model
    public $entity; // Related entity (Action, Document, etc.)

    public function __construct(User $mentionedUser, User $author, $comment, $entity)
    {
        $this->mentionedUser = $mentionedUser;
        $this->author = $author;
        $this->comment = $comment;
        $this->entity = $entity;
    }
}
