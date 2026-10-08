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
        'title',
        'version',
        'is_current',
        'workflow_status', // 'draft', 'verified_by_rq', 'approved_by_ceo'
        'work_unit_definition', // 'process', 'site', 'custom'
        'evaluation_date',
        'next_evaluation_date',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'evaluation_date' => 'date',
        'next_evaluation_date' => 'date',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dangers()
    {
        return $this->hasMany(DuerpDanger::class, 'duerp_id');
    }
}

