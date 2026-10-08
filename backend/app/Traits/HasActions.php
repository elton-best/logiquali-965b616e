<?php

namespace App\Traits;

use App\Models\Action;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasActions
{
    public function actions(): MorphToMany
    {
        return $this->morphToMany(Action::class, 'actionable');
    }

    public function addAction(Action|int $action): void
    {
        if (is_int($action)) {
            $action = Action::findOrFail($action);
        }
        $this->actions()->syncWithoutDetaching($action);
    }

    public function removeAction(Action|int $action): void
    {
        if (is_int($action)) {
            $action = Action::findOrFail($action);
        }
        $this->actions()->detach($action);
    }

    public function syncActions(array $actions): void
    {
        $this->actions()->sync($actions);
    }

    public function hasAction(int $actionId): bool
    {
        return $this->actions()->where('actions.id', $actionId)->exists();
    }

    public function scopeWithAction($query, int $actionId)
    {
        return $query->whereHas('actions', function ($q) use ($actionId) {
            $q->where('actions.id', $actionId);
        });
    }

    public function getPendingActionsCount(): int
    {
        return $this->actions()
            ->whereHas('workflowState', function ($q) {
                $q->where('is_final', false);
            })
            ->count();
    }

    public function getCompletedActionsCount(): int
    {
        return $this->actions()
            ->whereHas('workflowState', function ($q) {
                $q->where('code', 'completed');
            })
            ->count();
    }
}
