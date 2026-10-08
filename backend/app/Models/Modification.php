<?php

namespace App\Models;

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
        'site_id',
        'initiator_id',
        'number',
        'date',
        'object',
        'description',
        'scope',
        'objectives',
        'consequences',
        'required_resources',
        'document_ids',
        'responsible_id',
        'result_owner_id',
        'monitoring_owner_id',
        'validated_by',
        'validated_at',
        'status',
        'workflow_status',
        'approved_version',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'validated_at' => 'datetime',
            'approved_at' => 'datetime',
            'document_ids' => 'array',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function resultOwner()
    {
        return $this->belongsTo(User::class, 'result_owner_id');
    }

    public function monitoringOwner()
    {
        return $this->belongsTo(User::class, 'monitoring_owner_id');
    }
}
