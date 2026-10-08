<?php

namespace App\Observers;

use App\Models\CalibrationPlan;
use App\Traits\NotifiesSiteUsers;

class CalibrationPlanObserver
{
    use NotifiesSiteUsers;

    public function created(CalibrationPlan $calibrationPlan): void
    {
        if ($calibrationPlan->responsible_user_id) {
            $this->notifyUser($calibrationPlan->responsible_user_id, 'calibration_plan_created', [
                'type' => 'calibration_plan_created',
                'message' => "Plan d'étalonnage assigné: {$calibrationPlan->title}",
                'urgency' => 'high',
                'action_url' => "/company/calibration-plans/{$calibrationPlan->id}",
            ]);
        }
    }

    public function updated(CalibrationPlan $calibrationPlan): void
    {
        if ($calibrationPlan->wasChanged('status') && $calibrationPlan->responsible_user_id) {
            $this->notifyUser($calibrationPlan->responsible_user_id, 'calibration_plan_updated', [
                'type' => 'calibration_plan_updated',
                'message' => "Plan d'étalonnage {$calibrationPlan->title} - Statut: {$calibrationPlan->status}",
                'urgency' => 'info',
                'action_url' => "/company/calibration-plans/{$calibrationPlan->id}",
            ]);
        }

        if ($calibrationPlan->wasChanged('responsible_user_id')) {
            $oldResponsibleId = $calibrationPlan->getOriginal('responsible_user_id');
            if ($oldResponsibleId) {
                $this->notifyUser((int) $oldResponsibleId, 'calibration_plan_unassigned', [
                    'type' => 'calibration_plan_unassigned',
                    'message' => "Vous n'êtes plus responsable du plan d'étalonnage: {$calibrationPlan->title}",
                    'urgency' => 'info',
                    'action_url' => "/company/calibration-plans/{$calibrationPlan->id}",
                ]);
            }
            if ($calibrationPlan->responsible_user_id) {
                $this->notifyUser($calibrationPlan->responsible_user_id, 'calibration_plan_assigned', [
                    'type' => 'calibration_plan_assigned',
                    'message' => "Un plan d'étalonnage {$calibrationPlan->title} vous a été assigné",
                    'urgency' => 'high',
                    'action_url' => "/company/calibration-plans/{$calibrationPlan->id}",
                ]);
            }
        }
    }
}
