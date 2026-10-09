<?php

namespace App\Modules\Hse\Models;

use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Duerp extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'duerp';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'managing_process_id',
        'title',
        'version',
        'is_current',
        'workflow_status', // 'draft', 'submitted_by_rq', 'verified_by_rq', 'approved_by_ceo', 'rejected'
        'work_unit_definition', // 'process', 'site', 'custom'
        'evaluation_date',
        'next_evaluation_date',
        'submitted_by',
        'submitted_at',
        'submission_notes',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'evaluation_date' => 'date',
        'next_evaluation_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function managingProcess()
    {
        return $this->belongsTo(\App\Models\Process::class, 'managing_process_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function workUnits()
    {
        return $this->hasMany(DuerpWorkUnit::class, 'duerp_id');
    }

    public function dangers()
    {
        return $this->hasMany(DuerpDanger::class, 'duerp_id');
    }
}

