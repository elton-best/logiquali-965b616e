<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseSigleHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'enterprise_sigle_history';

    protected $fillable = [
        'enterprise_id',
        'old_sigle',
        'new_sigle',
        'reason',
        'recoding_mode',
        'equipements_affected',
        'is_reverted',
        'reverted_at',
        'reverted_by',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'equipements_affected' => 'integer',
            'is_reverted' => 'boolean',
            'reverted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relationships
    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function revertedBy()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    // Scopes
    public function scopeNotReverted($query)
    {
        return $query->where('is_reverted', false);
    }

    public function scopeReverted($query)
    {
        return $query->where('is_reverted', true);
    }

    public function scopeForEnterprise($query, $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }
}
