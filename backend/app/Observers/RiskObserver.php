<?php

namespace App\Observers;

use App\Models\Risk;
use App\Traits\NotifiesSiteUsers;
use Beg\SupervisionClient\Events\EventReporter;

class RiskObserver
{
    use NotifiesSiteUsers;

    public function created(Risk $risk): void
    {
        if (in_array($risk->criticality, ['critical', 'high']) && $risk->site_id) {
            $this->notifySiteUsers($risk->site_id, 'risk_created', [
                'type' => 'risk_created',
                'message' => "Nouveau risque {$risk->criticality}: {$risk->title}",
                'urgency' => $risk->criticality === 'critical' ? 'critical' : 'high',
                'action_url' => "/company/risks",
            ]);
        }

        EventReporter::record('risk.created', 'Nouveau risque identifié', [
            'category' => 'quality',
            'severity' => $risk->criticality === 'critical' ? 'critical' : ($risk->criticality === 'high' ? 'warning' : 'info'),
            'external_ref' => 'risk-created-'.$risk->id,
            'message' => "{$risk->title} ({$risk->criticality})",
            'metadata' => ['risk_id' => $risk->id, 'criticality' => $risk->criticality, 'site_id' => $risk->site_id],
        ]);
    }

    public function updated(Risk $risk): void
    {
        if ($risk->wasChanged('criticality') && in_array($risk->criticality, ['critical', 'high']) && $risk->site_id) {
            $this->notifySiteUsers($risk->site_id, 'risk_criticality_changed', [
                'type' => 'risk_criticality_changed',
                'message' => "Risque {$risk->title} - Criticité: {$risk->criticality}",
                'urgency' => $risk->criticality === 'critical' ? 'critical' : 'high',
                'action_url' => "/company/risks",
            ]);
        }
    }
}
