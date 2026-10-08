<?php

namespace App\Observers;

use App\Models\Communication;
use App\Traits\NotifiesSiteUsers;

class CommunicationObserver
{
    use NotifiesSiteUsers;

    public function created(Communication $communication): void
    {
        $userIds = array_filter([
            $communication->organizer_user_id,
            $communication->responsible_user_id,
        ]);

        // Ajouter participants
        if ($communication->participant_user_ids) {
            $userIds = array_merge($userIds, (array) $communication->participant_user_ids);
        }

        $this->notifyUsers(array_unique($userIds), 'communication_created', [
            'type' => 'communication_created',
            'message' => "Nouvelle communication/sensibilisation: {$communication->designation}",
            'urgency' => 'info',
            'action_url' => "/company/communications/{$communication->id}",
        ]);
    }

    public function updated(Communication $communication): void
    {
        // Notifier si l'organisateur change
        if ($communication->wasChanged('organizer_user_id')) {
            $oldOrganizerId = $communication->getOriginal('organizer_user_id');
            if ($oldOrganizerId) {
                $this->notifyUser((int) $oldOrganizerId, 'communication_organizer_unassigned', [
                    'type' => 'communication_organizer_unassigned',
                    'message' => "Vous n'êtes plus organisateur de la communication: {$communication->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/communications/{$communication->id}",
                ]);
            }
            if ($communication->organizer_user_id) {
                $this->notifyUser($communication->organizer_user_id, 'communication_organizer_changed', [
                    'type' => 'communication_organizer_changed',
                    'message' => "Vous êtes maintenant organisateur de la communication: {$communication->designation}",
                    'urgency' => 'high',
                    'action_url' => "/company/communications/{$communication->id}",
                ]);
            }
        }

        // Notifier si le chargé de communication change
        if ($communication->wasChanged('responsible_user_id')) {
            $oldResponsibleId = $communication->getOriginal('responsible_user_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'communication_responsible_unassigned', [
                    'type' => 'communication_responsible_unassigned',
                    'message' => "Vous n'êtes plus chargé de communication: {$communication->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/communications/{$communication->id}",
                ]);
            }
            if ($communication->responsible_user_id) {
                $this->notifyUser($communication->responsible_user_id, 'communication_responsible_assigned', [
                    'type' => 'communication_responsible_assigned',
                    'message' => "Vous êtes assigné comme chargé de communication: {$communication->designation}",
                    'urgency' => 'high',
                    'action_url' => "/company/communications/{$communication->id}",
                ]);
            }
        }

        // Notifier si les participants changent (nouveaux ajoutés)
        if ($communication->wasChanged('participant_user_ids')) {
            $oldIds = $communication->getOriginal('participant_user_ids') ? (array) $communication->getOriginal('participant_user_ids') : [];
            $newIds = $communication->participant_user_ids ? (array) $communication->participant_user_ids : [];
            $addedIds = array_diff($newIds, $oldIds);

            foreach ($addedIds as $userId) {
                $this->notifyUser($userId, 'communication_participant_added', [
                    'type' => 'communication_participant_added',
                    'message' => "Vous avez été ajouté à la communication: {$communication->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/communications/{$communication->id}",
                ]);
            }
        }

        // Notifier si le statut ou la date change
        if ($communication->wasChanged('status') || $communication->wasChanged('date_debut') || $communication->wasChanged('date_fin')) {
            $userIds = array_filter([
                $communication->organizer_user_id,
                $communication->responsible_user_id,
            ]);

            if ($communication->participant_user_ids) {
                $userIds = array_merge($userIds, (array) $communication->participant_user_ids);
            }

            $this->notifyUsers(array_unique($userIds), 'communication_updated', [
                'type' => 'communication_updated',
                'message' => "Communication {$communication->designation} a été mise à jour",
                'urgency' => 'info',
                'action_url' => "/company/communications/{$communication->id}",
            ]);
        }
    }
}
