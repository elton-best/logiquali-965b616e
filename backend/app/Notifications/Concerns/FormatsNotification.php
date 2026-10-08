<?php

namespace App\Notifications\Concerns;

trait FormatsNotification
{
    /**
     * Formate le sujet avec le préfixe BestQHSE
     */
    protected function formatSubject(string $subject): string
    {
        return "[BestQHSE] {$subject}";
    }

    /**
     * Formate la structure de base pour toArray() (database notification)
     */
    protected function formatBaseArray(
        string $type,
        int $entityId,
        string $ref,
        string $url,
        array $additionalData = []
    ): array {
        return array_merge([
            'type' => $type,
            'entity_id' => $entityId,
            'entity_ref' => $ref,
            'action_url' => $url,
            'created_at' => now()->toISOString(),
        ], $additionalData);
    }

    /**
     * Construit une URL vers le frontend
     */
    protected function buildActionUrl(string $path): string
    {
        $frontendUrl = app()->bound('config')
            ? config('app.frontend_url', 'http://localhost:3000')
            : 'http://localhost:3000';
        return rtrim($frontendUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Formate une salutation standard
     */
    protected function formatSalutation(): string
    {
        return "Cordialement,\nL'équipe BestQHSE";
    }
}
