<?php

namespace App\Modules\Leadership\Models;

use App\Models\Enterprise;
use App\Models\Process;
use App\Models\User;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Responsibility extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'process_id',
        'user_id',
        'level',
        'roles',
        'deliverables',
        'responsible_type',
        'responsible_id',
        'role_title',
        'responsibilities',
        'authorities',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'responsibilities' => 'array',
        'authorities' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responsible()
    {
        return $this->morphTo(__FUNCTION__, 'responsible_type', 'responsible_id');
    }
}
