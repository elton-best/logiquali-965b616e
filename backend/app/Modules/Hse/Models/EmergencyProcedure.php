<?php

namespace App\Modules\Hse\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyProcedure extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'emergency_type',
        'title',
        'procedure_steps',
        'emergency_contacts',
        'required_equipment',
        'training_required',
        'trained_users',
        'last_drill_date',
        'next_drill_date',
        'document_path',
    ];

    protected $casts = [
        'procedure_steps' => 'array',
        'emergency_contacts' => 'array',
        'required_equipment' => 'array',
        'training_required' => 'boolean',
        'trained_users' => 'array',
        'last_drill_date' => 'date',
        'next_drill_date' => 'date',
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
