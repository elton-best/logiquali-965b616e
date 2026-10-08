<?php

namespace App\Modules\Planning\Services;

use App\Models\Action;
use App\Services\ActionService;
use App\Services\IndicateurService;

use App\Models\Objective;
use Illuminate\Support\Facades\DB;

class ObjectiveService
{
    public function create(array $data): Objective
    {
        return DB::transaction(function () use ($data) {
            $objective = Objective::create([
                'ref' => $this->generateReference(),
                'site_id' => $data['site_id'],
                'process_id' => $data['process_id'] ?? null,
                'strategic_axis_id' => $data['strategic_axis_id'] ?? null,
                'indicateur_id' => $data['indicateur_id'] ?? null,
                'type' => $data['type'] ?? 'operational',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? now(),
                'target_value' => $data['target_value'],
                'responsible_id' => $data['responsible_id'] ?? null,
                'deadline' => $data['deadline'],
                'allocated_budget' => $data['allocated_budget'] ?? null,
                'required_resources' => $data['required_resources'] ?? null,
            ]);

            // Validation SMART si fournie
            if (isset($data['smart_criteria'])) {
                $objective->update([
                    'is_smart_validated' => $this->validateSMART($data['smart_criteria']),
                    'smart_criteria' => $data['smart_criteria'],
                ]);
            }

            if (isset($data['axes'])) {
                $objective->syncAxes($data['axes']);
            }

            // Normalize planned_actions into Action records when provided
            if (!empty($data['planned_actions']) && is_array($data['planned_actions'])) {
                $this->syncPlannedActions($objective, $data['planned_actions']);
                // Recalculate progress from actions
                $objective->refresh();
                $objective->updateProgress();
            }

            activity()
                ->performedOn($objective)
                ->causedBy(auth()->user())
                ->log('Objectif créé');

            return $objective->load(['axes', 'workflowState', 'indicateur', 'actions', 'responsible']);
        });
    }

    public function update(Objective $objective, array $data): Objective
    {
        $objective->update($data);

        if (isset($data['axes'])) {
            $objective->syncAxes($data['axes']);
        }

        // Normalize planned_actions into Action records if provided
        if (array_key_exists('planned_actions', $data)) {
            $planned = is_array($data['planned_actions']) ? $data['planned_actions'] : [];
            $this->syncPlannedActions($objective, $planned);
            $objective->refresh();
            $objective->updateProgress();
        }

        // Recalculer progression si valeur actuelle change (fallback)
        if (isset($data['current_value']) && empty($data['planned_actions'])) {
            $objective->updateProgress();
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log('Objectif mis à jour');

        return $objective->fresh();
    }

    public function activate(Objective $objective): Objective
    {
        if ($objective->canTransitionTo('active')) {
            $objective->transitionTo('active');
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log('Objectif activé');

        return $objective->fresh();
    }

    public function updateValue(Objective $objective, float $currentValue, ?string $note = null): Objective
    {
        $objective->update(['current_value' => $currentValue]);
        $objective->updateProgress();

        // Mettre à jour l'indicateur lié si existe
        if ($objective->indicateur) {
            app(IndicateurService::class)->addValue($objective->indicateur, $currentValue, now()->format('Y-m-d'), $note);
        }

        // Vérifier si atteint
        if ($objective->isAchieved() && $objective->canTransitionTo('achieved')) {
            $objective->transitionTo('achieved');
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log("Valeur actuelle mise à jour : {$currentValue} ({$objective->progress}% de l'objectif)");

        return $objective->fresh();
    }

    public function addMilestone(Objective $objective, array $milestoneData): Objective
    {
        $milestones = $objective->milestones ?? [];
        $milestones[] = array_merge($milestoneData, [
            'added_by' => auth()->id(),
            'added_at' => now()->toDateTimeString(),
        ]);

        $objective->update(['milestones' => $milestones]);

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log("Jalon ajouté : {$milestoneData['description']}");

        return $objective->fresh();
    }

    public function updateMilestoneStatus(Objective $objective, int $milestoneIndex, string $status): Objective
    {
        $milestones = $objective->milestones ?? [];
        
        if (isset($milestones[$milestoneIndex])) {
            $milestones[$milestoneIndex]['status'] = $status;
            $milestones[$milestoneIndex]['updated_at'] = now()->toDateTimeString();
            $milestones[$milestoneIndex]['updated_by'] = auth()->id();

            $objective->update(['milestones' => $milestones]);

            activity()
                ->performedOn($objective)
                ->causedBy(auth()->user())
                ->log("Jalon {$milestoneIndex} : {$status}");
        }

        return $objective->fresh();
    }

    public function achieve(Objective $objective): Objective
    {
        if ($objective->canTransitionTo('achieved')) {
            $objective->transitionTo('achieved');
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log('Objectif atteint ! 🎯');

        return $objective->fresh();
    }

    public function markNotAchieved(Objective $objective, string $reason): Objective
    {
        if ($objective->canTransitionTo('not_achieved')) {
            $objective->transitionTo('not_achieved');
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log("Objectif non atteint : {$reason}");

        return $objective->fresh();
    }

    public function cancel(Objective $objective, string $reason): Objective
    {
        if ($objective->canTransitionTo('cancelled')) {
            $objective->transitionTo('cancelled');
        }

        activity()
            ->performedOn($objective)
            ->causedBy(auth()->user())
            ->log("Objectif annulé : {$reason}");

        return $objective->fresh();
    }

    protected function validateSMART(array $criteria): bool
    {
        $required = ['specific', 'measurable', 'achievable', 'relevant', 'time_bound'];
        
        foreach ($required as $criterion) {
            if (empty($criteria[$criterion])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sync planned actions payload into Action records linked to the objective.
     * Format expected for each action:
     *  - id (optional): existing action id to update
     *  - title, description, responsible_user_id, start_date, due_date, status, progress
     */
    protected function syncPlannedActions(Objective $objective, array $plannedActions): void
    {
        $actionService = app(\App\Services\ActionService::class);

        // collect existing objective-sourced actions
        $existing = $objective->actions()->where('source_type', 'objective')->where('source_id', $objective->id)->get()->keyBy('id');

        $incomingIds = [];

        foreach ($plannedActions as $item) {
            $item = is_array($item) ? $item : [];

            // map possible keys
            $title = $item['title'] ?? ($item['label'] ?? 'Action lié à l\'objectif');
            $description = $item['description'] ?? 'Action liée à un objectif';
            $responsibleId = $item['responsible_user_id'] ?? $item['responsible_id'] ?? $objective->responsible_id ?? auth()->id();
            $startDate = $item['start_date'] ?? $item['start'] ?? null;
            $dueDate = $item['due_date'] ?? $item['deadline'] ?? $item['target_date'] ?? null;
            $status = $item['status'] ?? null;
            $progress = isset($item['progress']) ? (int) $item['progress'] : null;
            $normalizedStatus = $this->normalizeObjectiveActionStatus($status);

            if (!empty($item['id']) && isset($existing[$item['id']])) {
                // update existing action
                $action = $existing[$item['id']];

                $updates = array_filter([
                    'title' => $title,
                    'description' => $description,
                    'responsible_id' => $responsibleId,
                    'start_date' => $startDate,
                    'deadline' => $dueDate,
                    'status' => $normalizedStatus,
                ], fn($v) => $v !== null);

                // enforce status/progress rules: non démarré -> 0, completed -> 100
                if ($normalizedStatus === 'planned') {
                    $updates['progress'] = 0;
                } elseif ($normalizedStatus === 'completed') {
                    $updates['progress'] = 100;
                } elseif ($progress !== null) {
                    $updates['progress'] = min(100, max(0, $progress));
                }

                $action->update($updates);
                $incomingIds[] = $action->id;

            } else {
                // create new action
                $payload = [
                    'site_id' => $objective->site_id,
                    'process_id' => $objective->process_id,
                    'source_type' => 'objective',
                    'source_id' => $objective->id,
                    'type' => 'improvement',
                    'title' => $title,
                    'description' => $description,
                    'responsible_id' => $responsibleId,
                    'start_date' => $startDate,
                    'deadline' => $dueDate,
                    'status' => $normalizedStatus ?: 'planned',
                ];

                // Initial progress
                if ($normalizedStatus === 'completed') {
                    $payload['progress'] = 100;
                } elseif ($normalizedStatus === 'planned') {
                    $payload['progress'] = 0;
                } elseif ($progress !== null) {
                    $payload['progress'] = min(100, max(0, $progress));
                }

                $action = $actionService->create($payload);
                // attach action to objective polymorphically
                $objective->addAction($action);
                $incomingIds[] = $action->id;
            }
        }

        // Remove any existing objective-sourced actions not present in incoming payload
        $toRemove = $existing->keys()->filter(fn($id) => !in_array($id, $incomingIds));
        if ($toRemove->count() > 0) {
            foreach ($toRemove as $id) {
                $act = $existing[$id];
                // Only delete actions that were created from planned_actions (source_type/objective)
                try {
                    $act->delete();
                } catch (\Throwable $e) {
                    // ignore deletion errors
                }
            }
        }
    }

    private function normalizeObjectiveActionStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            'a_faire', 'todo', 'draft', 'planned', '' => 'planned',
            'en_cours', 'in_progress', 'inprogress' => 'in_progress',
            'terminee', 'terminée', 'done', 'completed' => 'completed',
            'verified' => 'verified',
            'cancelled', 'canceled' => 'cancelled',
            default => 'planned',
        };
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Objective::query();

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        $objectives = $query->get();

        return [
            'total' => $objectives->count(),
            'active' => $objectives->filter(fn($o) => $o->isInState('active'))->count(),
            'achieved' => $objectives->filter(fn($o) => $o->isInState('achieved'))->count(),
            'not_achieved' => $objectives->filter(fn($o) => $o->isInState('not_achieved'))->count(),
            'overdue' => $objectives->filter->isOverdue()->count(),
            'avg_progress' => round($objectives->avg('progress') ?? 0, 1),
            'by_type' => $objectives->groupBy('type')->map->count(),
            'smart_validated' => $objectives->where('is_smart_validated', true)->count(),
        ];
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastObjective = Objective::where('ref', 'like', "OBJ-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastObjective) {
            $lastNumber = (int) substr($lastObjective->ref, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('OBJ-%d-%03d', $year, $newNumber);
    }
}
