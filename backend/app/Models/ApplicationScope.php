<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationScope extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'version',
        'is_current',
        'objective',
        'scope',
        'document_objective',
        'scope_definition',
        'referenced_documents',
        'processes',
        'included_processes',
        'products_services',
        'organizational_units',
        'locations',
        'exclusions',
        'scope_exclusions',
        'exclusions_justification',
        'iso_exclusions',
        'iso_exclusions_justification',
        'norm_exclusions',
        'norm_exclusions_justifications',
        'applicable_norms',
        'document_generated',
        'document_path',
        'metadata',
        'generated_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'referenced_documents' => 'array',
        'processes' => 'array',
        'included_processes' => 'array',
        'products_services' => 'array',
        'organizational_units' => 'array',
        'locations' => 'array',
        'applicable_norms' => 'array',
        'norm_exclusions' => 'array',
        'norm_exclusions_justifications' => 'array',
        'document_generated' => 'boolean',
        'metadata' => 'array',
        'generated_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
