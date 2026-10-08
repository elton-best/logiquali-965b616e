<?php

namespace App\Broadcasting;

use App\Models\Document;
use App\Models\User;

class DocumentChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, int $documentId): array|bool
    {
        $document = Document::find($documentId);

        if (! $document) {
            return false;
        }

        // Check if user has permission to view this document
        if (! $user->can('documents.read')) {
            return false;
        }

        // Return user data to be broadcast to other users
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'color' => $this->generateUserColor($user->id),
        ];
    }

    /**
     * Generate a consistent color for a user based on their ID
     */
    private function generateUserColor(int $userId): string
    {
        $colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
        ];

        return $colors[$userId % count($colors)];
    }
}
