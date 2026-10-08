<?php

namespace App\Traits;

use App\Models\WorkflowState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasWorkflowStates
{
    public function workflowState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class);
    }

    public function transitionTo(string $stateCode): bool
    {
        $currentState = $this->workflowState;
        $entityType = class_basename($this);

        $newState = WorkflowState::where('entity_type', $entityType)
            ->where('code', $stateCode)
            ->where('is_active', true)
            ->firstOrFail();

        if ($currentState) {
            $allowedTransitions = $currentState->allowed_transitions ?? [];
            if (!in_array($stateCode, $allowedTransitions)) {
                throw new \Exception("Transition from {$currentState->code} to {$stateCode} is not allowed");
            }
        }

        $this->workflow_state_id = $newState->id;
        return $this->save();
    }

    public function canTransitionTo(string $stateCode): bool
    {
        $currentState = $this->workflowState;
        if (!$currentState) {
            return true;
        }

        $allowedTransitions = $currentState->allowed_transitions ?? [];
        return in_array($stateCode, $allowedTransitions);
    }

    public function isInState(string $stateCode): bool
    {
        return $this->workflowState && $this->workflowState->code === $stateCode;
    }

    public function isInitial(): bool
    {
        return $this->workflowState && $this->workflowState->is_initial;
    }

    public function isFinal(): bool
    {
        return $this->workflowState && $this->workflowState->is_final;
    }

    public function getAllowedTransitions(): array
    {
        if (!$this->workflowState) {
            $entityType = class_basename($this);
            return WorkflowState::where('entity_type', $entityType)
                ->where('is_initial', true)
                ->pluck('code')
                ->toArray();
        }

        return $this->workflowState->allowed_transitions ?? [];
    }

    public function scopeInState($query, string $stateCode)
    {
        return $query->whereHas('workflowState', function ($q) use ($stateCode) {
            $q->where('code', $stateCode);
        });
    }

    public function scopeInStates($query, array $stateCodes)
    {
        return $query->whereHas('workflowState', function ($q) use ($stateCodes) {
            $q->whereIn('code', $stateCodes);
        });
    }

    public static function bootHasWorkflowStates()
    {
        static::creating(function ($model) {
            if (!$model->workflow_state_id) {
                $entityType = class_basename($model);
                $initialState = WorkflowState::where('entity_type', $entityType)
                    ->where('is_initial', true)
                    ->where('is_active', true)
                    ->first();

                if ($initialState) {
                    $model->workflow_state_id = $initialState->id;
                }
            }
        });
    }
}
