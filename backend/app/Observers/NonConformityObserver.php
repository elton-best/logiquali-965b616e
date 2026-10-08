<?php

namespace App\Observers;

use App\Models\NonConformity;
use App\Traits\NotifiesSiteUsers;
use Beg\SupervisionClient\Events\EventReporter;

class NonConformityObserver
{
    use NotifiesSiteUsers;

    public function created(NonConformity $nonConformity): void
    {
        $this->notifySiteUsers($nonConformity->site_id, 'non_conformity_created', [
            'type' => 'non_conformity_created',
            'message' => "Nouvelle non-conformité: {$nonConformity->reference}",
            'urgency' => $nonConformity->severity === 'critical' ? 'critical' : 'high',
            'action_url' => "/company/nonconformities/{$nonConformity->id}",
        ]);

        EventReporter::record('nonconformity.created', 'Nouvelle non-conformité', [
            'category' => 'quality',
            'severity' => $nonConformity->severity === 'critical' ? 'critical' : 'warning',
            'external_ref' => 'nc-created-'.$nonConformity->id,
            'message' => "NC {$nonConformity->reference}",
            'metadata' => ['nonconformity_id' => $nonConformity->id, 'severity' => $nonConformity->severity, 'site_id' => $nonConformity->site_id],
        ]);
    }

    public function updated(NonConformity $nonConformity): void
    {
        if ($nonConformity->wasChanged('status')) {
            // Notifier le responsable
            if ($nonConformity->responsible_id) {
                $this->notifyUser($nonConformity->responsible_id, 'non_conformity_updated', [
                    'type' => 'non_conformity_updated',
                    'message' => "NC {$nonConformity->reference} - Statut: {$nonConformity->status}",
                    'urgency' => 'info',
                    'action_url' => "/company/nonconformities/{$nonConformity->id}",
                ]);
            }
        }

        // Notifier si le responsable change
        if ($nonConformity->wasChanged('responsible_id')) {
            if ($nonConformity->responsible_id) {
                $this->notifyUser($nonConformity->responsible_id, 'non_conformity_assigned', [
                    'type' => 'non_conformity_assigned',
                    'message' => "Une non-conformité {$nonConformity->reference} vous a été assignée",
                    'urgency' => 'high',
                    'action_url' => "/company/nonconformities/{$nonConformity->id}",
                ]);
            }
        }
    }

    public function deleted(NonConformity $nonConformity): void
    {
        // Notifier si important
        if ($nonConformity->responsible_id) {
            $this->notifyUser($nonConformity->responsible_id, 'non_conformity_deleted', [
                'type' => 'non_conformity_deleted',
                'message' => "NC {$nonConformity->reference} supprimée",
                'urgency' => 'info',
            ]);
        }
    }
}
