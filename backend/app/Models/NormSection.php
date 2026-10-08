<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NormSection extends Model
{
    protected $fillable = [
        'norm_version_id',
        'parent_id',
        'path',
        'level',
        'type',
        'number',
        'title',
        'content',
        'order_index',
        'references',
        'metadata',
    ];

    protected $casts = [
        'level' => 'integer',
        'order_index' => 'integer',
        'references' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function normVersion()
    {
        return $this->belongsTo(NormVersion::class);
    }

    public function parent()
    {
        return $this->belongsTo(NormSection::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(NormSection::class, 'parent_id')->orderBy('order_index');
    }

    public function annotations()
    {
        return $this->hasMany(NormAnnotation::class);
    }

    /**
     * Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Helpers
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . ($this->title ?: $this->number);
        }
        return $this->title ?: $this->number;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getChildrenCount(): int
    {
        return $this->children()->count();
    }

    public function getAllDescendants()
    {
        return NormSection::where('path', 'like', $this->path . '.%')
            ->where('norm_version_id', $this->norm_version_id)
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Build path from parent
     */
    public static function buildPath(?NormSection $parent, string $number): string
    {
        if ($parent) {
            return $parent->path . '.' . $number;
        }
        return $number;
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        $icons = [
            'chapter' => 'mdi-book-outline',
            'subchapter' => 'mdi-book-open-outline',
            'paragraph' => 'mdi-text-box-outline',
            'point' => 'mdi-circle-small',
            'note' => 'mdi-information-outline',
            'annex' => 'mdi-paperclip',
        ];

        return $icons[$this->type] ?? 'mdi-file-document-outline';
    }
}

