<?php

namespace App\Modules\Leadership\Models;

use Illuminate\Database\Eloquent\Model;

class NormAnnotation extends Model
{
    protected $fillable = [
        'norm_section_id',
        'user_id',
        'enterprise_id',
        'content',
        'is_private',
        'type',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    /**
     * Relations
     */
    public function normSection()
    {
        return $this->belongsTo(NormSection::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    /**
     * Scopes
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopeByEnterprise($query, $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

