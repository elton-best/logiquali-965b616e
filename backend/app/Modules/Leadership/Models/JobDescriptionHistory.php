<?php

namespace App\Modules\Leadership\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDescriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_description_id',
        'enterprise_id',
        'site_id',
        'field_key',
        'old_value',
        'new_value',
        'changed_by',
        'change_source',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
