<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_category_id',
        'name',
        'steps',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the document category this workflow belongs to
     */
    public function documentCategory()
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    /**
     * Get all documents using this workflow
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'workflow_id');
    }

    /**
     * Scope: active workflows only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: default workflows
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the number of steps in this workflow
     */
    public function getStepsCountAttribute(): int
    {
        return is_array($this->steps) ? count($this->steps) : 0;
    }

    /**
     * Get workflow step by order
     */
    public function getStepByOrder(int $order): ?array
    {
        if (!is_array($this->steps)) return null;
        
        foreach ($this->steps as $step) {
            if (isset($step['order']) && $step['order'] === $order) {
                return $step;
            }
        }
        
        return null;
    }

    /**
     * Check if a role is required in this workflow
     */
    public function requiresRole(string $role): bool
    {
        if (!is_array($this->steps)) return false;
        
        foreach ($this->steps as $step) {
            if (isset($step['role']) && $step['role'] === $role && ($step['required'] ?? false)) {
                return true;
            }
        }
        
        return false;
    }
}
