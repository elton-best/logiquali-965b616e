<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParticipationRecord extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'record_type',
        'subject',
        'description',
        'date',
        'participants',
        'attendance_list_path',
        'attendance_proof_path',
        'decisions',
        'action_items',
    ];

    protected $casts = [
        'date' => 'date',
        'participants' => 'array',
        'action_items' => 'array',
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
