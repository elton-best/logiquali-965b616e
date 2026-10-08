<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\SiteEventNotification;

trait NotifiesSiteUsers
{
    /**
     * Notifier tous les utilisateurs actifs d'un site
     * Utilise uniquement la table notifications Laravel (via ->notify()).
     * 
     * @param int $siteId
     * @param string $type Type d'événement (action_assigned, formation_scheduled, etc.)
     * @param array $data Données de l'événement
     * @param int|null $actorId ID de l'utilisateur responsable de l'action
     * @param bool $isSystemNotification Si true, n'ajoute pas l'actor aux données
     */
    protected function notifySiteUsers(
        int $siteId,
        string $type,
        array $data,
        ?int $actorId = null,
        bool $isSystemNotification = false
    ): void {
        // Broadcast interdit hors notifications système explicites.
        if (!$isSystemNotification) {
            return;
        }

        // Ne pas ajouter l'acteur pour les notifications système
        if (!$isSystemNotification) {
            $actor = $actorId ? User::find($actorId) : auth()->user();
            
            if ($actor) {
                $data['actor_name'] = $actor->name;
                $data['actor_id'] = $actor->id;
            }
        }

        $users = User::where('site_id', $siteId)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            $user->notify(new SiteEventNotification($type, $data, $data['actor_id'] ?? null));
        }
    }

    /**
     * Notifier des utilisateurs spécifiques (par ID)
     * Utilisé pour notifications ciblées (assignation, participation, approbation)
     * 
     * Utilise uniquement la table notifications Laravel (ciblée)
     * 
     * @param array $userIds IDs des utilisateurs à notifier
     * @param string $type Type d'événement
     * @param array $data Données de l'événement
     * @param int|null $actorId ID de l'utilisateur responsable de l'action
     */
    protected function notifyUsers(
        array $userIds,
        string $type,
        array $data,
        ?int $actorId = null
    ): void {
        if (empty($userIds)) {
            return;
        }

        $actor = $actorId ? User::find($actorId) : auth()->user();

        if ($actor) {
            $data['actor_name'] = $actor->name;
            $data['actor_id'] = $actor->id;
        }

        $entityId = (string)($data['id'] ?? $data['entity_id'] ?? '');
        $dedupKey = hash('sha256', "{$type}|{$entityId}");
        $data['dedup_key'] = $dedupKey;

        // Filtrer les IDs valides et créer les notifications
        User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->get()
            ->each(function ($user) use ($type, $data, $dedupKey) {
                $exists = $user->notifications()
                    ->where('type', SiteEventNotification::class)
                    ->where('created_at', '>=', now()->subSeconds(5))
                    ->whereRaw("(data::jsonb->>'dedup_key') = ?", [$dedupKey])
                    ->exists();

                if ($exists) {
                    return;
                }

                $user->notify(new SiteEventNotification($type, $data, $data['actor_id'] ?? null));
            });
    }

    /**
     * Notifier un utilisateur spécifique
     */
    protected function notifyUser(
        int $userId,
        string $type,
        array $data,
        ?int $actorId = null
    ): void {
        $this->notifyUsers([$userId], $type, $data, $actorId);
    }
}
