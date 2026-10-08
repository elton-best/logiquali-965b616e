<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessIsoCoverage extends Model
{
    use HasFactory;

    protected $table = 'process_iso_coverage';

    protected $fillable = [
        'process_id',
        'norme',
        'version',
        'clause_number',
        'clause_title',
        'clause_description',
        'coverage_level',
        'coverage_percentage',
        'coverage_comment',
        'evidence',
        'conformity_status',
        'last_audit_date',
        'next_audit_date',
    ];

    protected $casts = [
        'evidence' => 'array',
        'last_audit_date' => 'date',
        'next_audit_date' => 'date',
    ];

    // Relations
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    // Scopes
    public function scopeByNorme($query, $norme)
    {
        return $query->where('norme', $norme);
    }

    public function scopeFullCoverage($query)
    {
        return $query->where('coverage_level', 'full');
    }

    public function scopeNonConforme($query)
    {
        return $query->where('conformity_status', 'non_conforme');
    }
}
