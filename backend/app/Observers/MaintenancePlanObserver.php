<?php

namespace App\Observers;

use App\Models\MaintenancePlan;
use App\Traits\NotifiesSiteUsers;

class MaintenancePlanObserver
{
    use NotifiesSiteUsers;

    public function created(MaintenancePlan $maintenancePlan): void
    {
        if ($maintenancePlan->responsible_user_id) {
            $this->notifyUser($maintenancePlan->responsible_user_id, 'maintenance_plan_created', [
                'type' => 'maintenance_plan_created',
                'message' => "Plan de maintenance assigné: {$maintenancePlan->title}",
                'urgency' => 'high',
                'action_url' => "/company/maintenance-plans/{$maintenancePlan->id}",
            ]);
        }
    }

    public function updated(MaintenancePlan $maintenancePlan): void
    {
        if ($maintenancePlan->wasChanged('status') && $maintenancePlan->responsible_user_id) {
            $this->notifyUser($maintenancePlan->responsible_user_id, 'maintenance_plan_updated', [
                'type' => 'maintenance_plan_updated',
                'message' => "Plan de maintenance {$maintenancePlan->title} - Statut: {$maintenancePlan->status}",
                'urgency' => 'info',
                'action_url' => "/company/maintenance-plans/{$maintenancePlan->id}",
            ]);
        }

        if ($maintenancePlan->wasChanged('responsible_user_id')) {
            $oldResponsibleId = $maintenancePlan->getOriginal('responsible_user_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'maintenance_plan_unassigned', [
                    'type' => 'maintenance_plan_unassigned',
                    'message' => "Vous n'êtes plus responsable du plan de maintenance: {$maintenancePlan->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/maintenance-plans/{$maintenancePlan->id}",
                ]);
            }
            if ($maintenancePlan->responsible_user_id) {
                $this->notifyUser($maintenancePlan->responsible_user_id, 'maintenance_plan_assigned', [
                    'type' => 'maintenance_plan_assigned',
                    'message' => "Un plan de maintenance {$maintenancePlan->title} vous a été assigné",
                    'urgency' => 'high',
                    'action_url' => "/company/maintenance-plans/{$maintenancePlan->id}",
                ]);
            }
        }
    }
}
