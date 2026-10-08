<?php

namespace App\Listeners\Comment;

use App\Events\Comment\UserMentioned;
use App\Notifications\Comment\MentionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMentionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
    }

    public function handle(UserMentioned $event): void
    {
        $event->mentionedUser->notify(
            new MentionNotification(
                $event->author,
                $event->comment,
                $event->entity
            )
        );
    }
}
