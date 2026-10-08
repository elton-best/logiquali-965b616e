<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'level',
        'description',
        'retention_period_years',
        'required_approvers',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_approvers' => 'array',
            'is_active' => 'boolean',
            'level' => 'integer',
            'retention_period_years' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Get all documents in this category
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    /**
     * Get workflows for this category
     */
    public function workflows()
    {
        return $this->hasMany(DocumentWorkflow::class);
    }

    /**
     * Get default workflow for this category
     */
    public function defaultWorkflow()
    {
        return $this->hasOne(DocumentWorkflow::class)->where('is_default', true);
    }

    /**
     * Scope: active categories only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: order by level
     */
    public function scopeByLevel($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Check if this is the highest level (Politique)
     */
    public function isTopLevel(): bool
    {
        return $this->level === 1;
    }

    /**
     * Get category name with level indicator
     */
    public function getFullNameAttribute(): string
    {
        return "Niveau {$this->level} - {$this->name}";
    }
}
