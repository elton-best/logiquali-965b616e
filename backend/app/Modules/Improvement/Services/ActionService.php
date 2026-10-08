<?php

namespace App\Modules\Improvement\Services;

use App\Models\NonConformity;
use App\Models\Plan;

use App\Models\Action;
use App\Models\PlanAction;
use Illuminate\Support\Facades\DB;

class ActionService
{
    public function create(array $data): Action
    {
        $normalizedType = $this->normalizeActionType($data['type'] ?? null);
        $normalizedStatus = $this->normalizeActionStatus($data['status'] ?? null);

        return DB::transaction(function () use ($data, $normalizedType, $normalizedStatus) {
            $action = Action::create([
                'ref' => $this->generateReference(),
                'site_id' => $data['site_id'],
                'process_id' => $data['process_id'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'plan_action_id' => $data['plan_action_id'] ?? null,
                'type' => $normalizedType,
                'priority' => $data['priority'] ?? 'medium',
                'title' => $data['title'],
                'description' => $data['description'] ?? 'Action générée automatiquement',
                'source' => $data['source'] ?? null,
                'initiator_id' => $data['initiator_id'] ?? auth()->id(),
                'responsible_id' => $data['responsible_id'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'required_resources' => $data['required_resources'] ?? null,
                'status' => $normalizedStatus,
                'progress' => isset($data['progress']) ? (int) $data['progress'] : null,
                'progress_rate' => isset($data['progress_rate']) ? (int) $data['progress_rate'] : (isset($data['progress']) ? (int) $data['progress'] : null),
            ]);

            // Lier aux entités sources (polymorphique)
            if (isset($data['actionables'])) {
                $this->syncActionables($action, $data['actionables']);
            }

            activity()
                ->performedOn($action)
                ->causedBy(auth()->user())
                ->log('Action créée');

            return $action->load('workflowState');
        });
    }

    public function update(Action $action, array $data): Action
    {
        if (array_key_exists('type', $data)) {
            $data['type'] = $this->normalizeActionType($data['type']);
        }
        if (array_key_exists('status', $data)) {
            $data['status'] = $this->normalizeActionStatus($data['status']);
        }

        $action->update($data);

        if (isset($data['actionables'])) {
            $this->syncActionables($action, $data['actionables']);
        }

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Action mise à jour');

        return $action->fresh();
    }

    public function plan(Action $action, array $planData): Action
    {
        $action->update([
            'responsible_id' => $planData['responsible_id'],
            'deadline' => $planData['deadline'],
            'estimated_cost' => $planData['estimated_cost'] ?? null,
            'required_resources' => $planData['required_resources'] ?? null,
        ]);

        if ($action->canTransitionTo('planned')) {
            $action->transitionTo('planned');
        }

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Action planifiée');

        return $action->fresh();
    }

    public function start(Action $action): Action
    {
        if ($action->canTransitionTo('in_progress')) {
            $action->transitionTo('in_progress');
        }

        $action->addProgressNote('Action démarrée');

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Action démarrée');

        return $action->fresh();
    }

    public function updateProgress(Action $action, int $progress, ?string $note = null): Action
    {
        \Illuminate\Support\Facades\Log::debug('ActionService::updateProgress enter', ['action_id' => $action->id, 'status' => $action->status, 'requested_progress' => $progress, 'user' => auth()->id()]);
        try {
            // Enforce E1 rules:
            // - Non démarré (planned/draft/a_faire) => progress stays 0 and not modifiable
            // - Terminé (completed/terminee) => progress forced to 100
            // - En cours => manual progress allowed
            $status = $action->status;
            $normalizedProgress = min(100, max(0, $progress));

            // Only treat as completion when requested progress is 100.
            if ($normalizedProgress === 100) {
                try {
                    $result = $this->complete($action);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ActionService::updateProgress: transition to completed failed', ['action_id' => $action->id, 'error' => $e->getMessage()]);
                    // Fallback: force progress and continue
                    $action->update(['progress' => 100]);
                    $result = $action->fresh();
                }

                if ($note) {
                    $result->addProgressNote($note);
                }

                activity()
                    ->performedOn($result)
                    ->causedBy(auth()->user())
                    ->log("Avancement mis à jour : 100%");

                \Illuminate\Support\Facades\Log::debug('ActionService::updateProgress completed branch', ['action_id' => $action->id]);

                return $result;
            }

            // If progress > 0 and action not yet started, move it to in_progress so progress can be manually updated
            if ($normalizedProgress > 0 && !$action->isInState('in_progress') && !$action->isInState('completed')) {
                try {
                    $action = $this->start($action);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ActionService::updateProgress: transition to in_progress failed', ['action_id' => $action->id, 'error' => $e->getMessage()]);
                    // proceed without state transition
                }
            }

            // If normalizedProgress is 0, keep as-is
            $action->update(['progress' => $normalizedProgress]);

            if ($note) {
                $action->addProgressNote($note);
            }

            // Vérifier si en retard
            if ($action->isOverdue() && !$action->isInState('delayed')) {
                $action->transitionTo('delayed');
            }

            activity()
                ->performedOn($action)
                ->causedBy(auth()->user())
                ->log("Avancement mis à jour : {$normalizedProgress}%");

            \Illuminate\Support\Facades\Log::debug('ActionService::updateProgress exit', ['action_id' => $action->id, 'progress' => $normalizedProgress]);

            return $action->fresh();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActionService::updateProgress error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function verify(Action $action, array $verificationData): Action
    {
        $action->update([
            'effectiveness_verified' => $verificationData['is_effective'],
            'verification_date' => now(),
        ]);

        if ($verificationData['is_effective']) {
            if ($action->canTransitionTo('in_verification')) {
                $action->transitionTo('in_verification');
            }
        } else {
            // Retour en cours si non efficace
            if ($action->canTransitionTo('in_progress')) {
                $action->transitionTo('in_progress');
            }
        }

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Efficacité vérifiée : ' . ($verificationData['is_effective'] ? 'OUI' : 'NON'));

        return $action->fresh();
    }

    public function complete(Action $action, ?float $actualCost = null): Action
    {
        $action->update([
            'progress' => 100,
            'actual_cost' => $actualCost ?? $action->actual_cost,
        ]);

        if ($action->canTransitionTo('completed')) {
            $action->transitionTo('completed');
        }

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Action complétée');

        return $action->fresh();
    }

    public function cancel(Action $action, string $reason): Action
    {
        if ($action->canTransitionTo('cancelled')) {
            $action->transitionTo('cancelled');
        }

        $action->addProgressNote("Action annulée : {$reason}");

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log("Action annulée : {$reason}");

        return $action->fresh();
    }

    public function approve(Action $action, ?string $notes = null): Action
    {
        $action->update([
            'approved_by' => auth()->id(),
            'approval_date' => now(),
            'approval_notes' => $notes,
        ]);

        activity()
            ->performedOn($action)
            ->causedBy(auth()->user())
            ->log('Action approuvée');

        return $action->fresh();
    }

    protected function syncActionables(Action $action, array $actionables): void
    {
        // Format: ['type' => 'NonConformity', 'id' => 1], ...
        foreach ($actionables as $actionable) {
            $modelClass = "App\\Models\\{$actionable['type']}";
            if (class_exists($modelClass)) {
                $model = $modelClass::find($actionable['id']);
                if ($model) {
                    $model->addAction($action);
                }
            }
        }
    }

    private function normalizeActionType(?string $type): string
    {
        $value = strtolower(trim((string) $type));

        return match ($value) {
            'corrective', 'preventive', 'improvement', 'immediate' => $value,
            'objective_action', 'objective', 'action_objectif' => 'improvement',
            default => 'improvement',
        };
    }

    private function normalizeActionStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            'planned', 'a_faire', 'todo', 'draft', '' => 'planned',
            'in_progress', 'en_cours', 'inprogress' => 'in_progress',
            'completed', 'terminee', 'terminée', 'done' => 'completed',
            'verified' => 'verified',
            'cancelled', 'canceled' => 'cancelled',
            default => 'planned',
        };
    }

    public function createPlan(array $data): PlanAction
    {
        return DB::transaction(function () use ($data) {
            $plan = PlanAction::create([
                'ref' => $this->generatePlanReference(),
                'site_id' => $data['site_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'responsible_id' => $data['responsible_id'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            activity()
                ->performedOn($plan)
                ->causedBy(auth()->user())
                ->log('Plan d\'actions créé');

            return $plan;
        });
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastAction = Action::withoutEnterpriseScope()
            ->withTrashed()
            ->where('ref', 'like', "ACT-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastAction) {
            $lastNumber = 0;
            if (preg_match('/(\d+)$/', (string) $lastAction->ref, $matches)) {
                $lastNumber = (int) $matches[1];
            }
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $width = max(3, strlen((string) $newNumber));
        $number = str_pad((string) $newNumber, $width, '0', STR_PAD_LEFT);

        return sprintf('ACT-%d-%s', $year, $number);
    }

    protected function generatePlanReference(): string
    {
        $year = now()->year;
        $lastPlan = PlanAction::withoutEnterpriseScope()
            ->withTrashed()
            ->where('ref', 'like', "PLAN-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastPlan) {
            $lastNumber = 0;
            if (preg_match('/(\d+)$/', (string) $lastPlan->ref, $matches)) {
                $lastNumber = (int) $matches[1];
            }
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $width = max(3, strlen((string) $newNumber));
        $number = str_pad((string) $newNumber, $width, '0', STR_PAD_LEFT);

        return sprintf('PLAN-%d-%s', $year, $number);
    }
}
