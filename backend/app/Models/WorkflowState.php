<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowState extends Model
{
    protected $fillable = [
        'entity_type',
        'code',
        'label',
        'color',
        'description',
        'order',
        'is_initial',
        'is_final',
        'allowed_transitions',
        'required_fields',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'is_active' => 'boolean',
        'allowed_transitions' => 'array',
        'required_fields' => 'array',
        'permissions' => 'array',
    ];

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInitial($query)
    {
        return $query->where('is_initial', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function canTransitionTo(WorkflowState $targetState): bool
    {
        return in_array($targetState->code, $this->allowed_transitions ?? []);
    }

    public function getNextStates()
    {
        if (empty($this->allowed_transitions)) {
            return collect();
        }

        return static::where('entity_type', $this->entity_type)
            ->whereIn('code', $this->allowed_transitions)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
}

