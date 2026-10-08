<?php

namespace App\Services;

use App\Models\User;
use App\Events\Comment\UserMentioned;

class MentionParser
{
    /**
     * Parse mentions dans le contenu et déclenche events
     */
    public static function parseMentions(string $content, User $author, $comment, $entity): array
    {
        // Regex pour détecter @username
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        
        $mentionedUsers = [];
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                // Chercher l'utilisateur par username ou email
                $user = User::where('username', $username)
                    ->orWhere('email', $username . '@example.com')
                    ->first();
                
                if ($user && $user->id !== $author->id) {
                    // Éviter de notifier l'auteur lui-même
                    event(new UserMentioned($user, $author, $comment, $entity));
                    $mentionedUsers[] = $user;
                }
            }
        }
        
        return $mentionedUsers;
    }

    /**
     * Remplace @username par des liens HTML
     */
    public static function formatMentions(string $content): string
    {
        return preg_replace(
            '/@([a-zA-Z0-9_]+)/',
            '<a href="/users/$1" class="mention">@$1</a>',
            $content
        );
    }
}
