<?php

namespace App\Modules\Planning\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modification extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'initiator_id',
        'number',
        'date',
        'object',
        'description',
        'objectives',
        'consequences',
        'required_resources',
        'affected_document_ids',
        'responsible_id',
        'validated_by',
        'validated_at',
        'status',
        'workflow_status', // brouillon, en_cours, verifie_rq, approuve_ceo, rejete
        'rq_verified_by',
        'rq_verified_at',
        'rq_notes',
        'ceo_approved_by',
        'ceo_approved_at',
        'ceo_notes',
        'modification_results',
        'surveillance_results',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'validated_at' => 'datetime',
            'rq_verified_at' => 'datetime',
            'ceo_approved_at' => 'datetime',
            'affected_document_ids' => 'array',
        ];
    }

    public function enterprise()
    {
        return $this->belongsTo(\App\Models\Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function initiator()
    {
        return $this->belongsTo(\App\Models\User::class, 'initiator_id');
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function rqVerifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'rq_verified_by');
    }

    public function ceoApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'ceo_approved_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getAffectedDocumentsAttribute()
    {
        if (empty($this->affected_document_ids)) {
            return collect();
        }
        return \App\Models\Document::whereIn('id', $this->affected_document_ids)->get();
    }
}

