<?php

namespace App\Observers;

use App\Models\Formation;
use App\Traits\NotifiesSiteUsers;

class FormationObserver
{
    use NotifiesSiteUsers;

    public function created(Formation $formation): void
    {
        $userIds = array_filter([
            $formation->organizer_user_id,
            $formation->formateur_user_id,
        ]);

        // Ajouter participants (target_user_ids est JSON array)
        if ($formation->target_user_ids) {
            $userIds = array_merge($userIds, (array) $formation->target_user_ids);
        }

        $this->notifyUsers(array_unique($userIds), 'formation_created', [
            'type' => 'formation_created',
            'message' => "Nouvelle formation: {$formation->designation}",
            'urgency' => 'info',
            'action_url' => "/company/competences/formations/{$formation->id}",
        ]);
    }

    public function updated(Formation $formation): void
    {
        // Notifier si l'organisateur change
        if ($formation->wasChanged('organizer_user_id')) {
            $oldOrganizerId = $formation->getOriginal('organizer_user_id');
            if ($oldOrganizerId) {
                $this->notifyUser((int) $oldOrganizerId, 'formation_organizer_unassigned', [
                    'type' => 'formation_organizer_unassigned',
                    'message' => "Vous n'êtes plus organisateur de la formation: {$formation->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/competences/formations/{$formation->id}",
                ]);
            }
            if ($formation->organizer_user_id) {
                $this->notifyUser($formation->organizer_user_id, 'formation_organizer_changed', [
                    'type' => 'formation_organizer_changed',
                    'message' => "Vous êtes maintenant organisateur de la formation: {$formation->designation}",
                    'urgency' => 'high',
                    'action_url' => "/company/competences/formations/{$formation->id}",
                ]);
            }
        }

        // Notifier si le formateur change
        if ($formation->wasChanged('formateur_user_id')) {
            $oldFormateurId = $formation->getOriginal('formateur_user_id');
            if ($oldFormateurId) {
                $this->notifyUser((int) $oldFormateurId, 'formation_formateur_unassigned', [
                    'type' => 'formation_formateur_unassigned',
                    'message' => "Vous n'êtes plus formateur de la formation: {$formation->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/competences/formations/{$formation->id}",
                ]);
            }
            if ($formation->formateur_user_id) {
                $this->notifyUser($formation->formateur_user_id, 'formation_formateur_assigned', [
                    'type' => 'formation_formateur_assigned',
                    'message' => "Vous êtes assigné comme formateur: {$formation->designation}",
                    'urgency' => 'high',
                    'action_url' => "/company/competences/formations/{$formation->id}",
                ]);
            }
        }

        // Notifier si les participants changent (nouveaux ajoutés)
        if ($formation->wasChanged('target_user_ids')) {
            $oldIds = $formation->getOriginal('target_user_ids') ? (array) $formation->getOriginal('target_user_ids') : [];
            $newIds = $formation->target_user_ids ? (array) $formation->target_user_ids : [];
            $addedIds = array_diff($newIds, $oldIds);

            foreach ($addedIds as $userId) {
                $this->notifyUser($userId, 'formation_participant_added', [
                    'type' => 'formation_participant_added',
                    'message' => "Vous avez été ajouté à la formation: {$formation->designation}",
                    'urgency' => 'info',
                    'action_url' => "/company/competences/formations/{$formation->id}",
                ]);
            }
        }

        // Notifier si le statut ou la date change
        if ($formation->wasChanged('status') || $formation->wasChanged('date_debut') || $formation->wasChanged('date_fin')) {
            $userIds = array_filter([
                $formation->organizer_user_id,
                $formation->formateur_user_id,
            ]);

            if ($formation->target_user_ids) {
                $userIds = array_merge($userIds, (array) $formation->target_user_ids);
            }

            $this->notifyUsers(array_unique($userIds), 'formation_updated', [
                'type' => 'formation_updated',
                'message' => "Formation {$formation->designation} a été mise à jour",
                'urgency' => 'info',
                'action_url' => "/company/competences/formations/{$formation->id}",
            ]);
        }
    }
}
