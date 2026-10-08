<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NormVersion extends Model
{
    protected $fillable = [
        'norm_id',
        'version_code',
        'full_code',
        'published_at',
        'archived_at',
        'excel_file_path',
        'is_current',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'date',
        'archived_at' => 'date',
        'is_current' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function norm()
    {
        return $this->belongsTo(Norm::class);
    }

    public function sections()
    {
        return $this->hasMany(NormSection::class)->orderBy('order_index');
    }

    public function rootSections()
    {
        return $this->sections()->whereNull('parent_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Helpers
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive()
    {
        $this->update([
            'archived_at' => now(),
            'is_current' => false,
        ]);
    }

    public function getTotalSectionsAttribute(): int
    {
        return $this->sections()->count();
    }

    public function getChaptersCountAttribute(): int
    {
        return $this->sections()->where('type', 'chapter')->count();
    }
}

