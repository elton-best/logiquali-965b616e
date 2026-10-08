<?php

namespace App\Observers;

use App\Models\Audit;
use App\Traits\NotifiesSiteUsers;
use Beg\SupervisionClient\Events\EventReporter;

class AuditObserver
{
    use NotifiesSiteUsers;

    public function created(Audit $audit): void
    {
        // Notifier uniquement l'assigné et le lead auditor
        $userIds = array_filter([
            $audit->assigned_to,
            $audit->lead_auditor_id,
        ]);

        $this->notifyUsers($userIds, 'audit_created', [
            'type' => 'audit_created',
            'message' => "Nouvel audit planifié: {$audit->title}",
            'urgency' => 'info',
            'action_url' => "/company/audits/{$audit->id}",
        ]);

        EventReporter::record('audit.created', 'Nouvel audit planifié', [
            'category' => 'audit',
            'severity' => 'info',
            'external_ref' => 'audit-created-'.$audit->id,
            'message' => $audit->title,
            'metadata' => ['audit_id' => $audit->id, 'site_id' => $audit->site_id],
        ]);
    }

    public function updated(Audit $audit): void
    {
        if ($audit->wasChanged('status')) {
            $urgency = $audit->status === 'completed' ? 'info' : 'high';

            $this->notifySiteUsers($audit->site_id, 'audit_status_changed', [
                'type' => 'audit_status_changed',
                'message' => "Audit {$audit->title} - Statut: {$audit->status}",
                'urgency' => $urgency,
                'action_url' => "/company/audits/{$audit->id}",
            ]);

            EventReporter::record('audit.status_changed', 'Statut audit modifié', [
                'category' => 'audit',
                'severity' => $audit->status === 'completed' ? 'info' : 'warning',
                'message' => "{$audit->title} → {$audit->status}",
                'metadata' => ['audit_id' => $audit->id, 'status' => $audit->status],
            ]);
        }

        // Notifier si la date change (risque de retard)
        if ($audit->wasChanged('scheduled_date') || $audit->wasChanged('end_date')) {
            $userIds = array_filter([
                $audit->assigned_to,
                $audit->lead_auditor_id,
            ]);

            $this->notifyUsers($userIds, 'audit_date_changed', [
                'type' => 'audit_date_changed',
                'message' => "Dates de l'audit {$audit->title} ont changé",
                'urgency' => 'high',
                'action_url' => "/company/audits/{$audit->id}",
            ]);
        }
    }
}
