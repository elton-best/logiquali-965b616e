<?php

namespace App\Modules\Hse\Models;

use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpWorkUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'duerp_work_units';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'duerp_id',
        'process_id',
        'name',
        'code',
        'description',
        'headcount',
        'manager_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headcount' => 'integer',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function duerp()
    {
        return $this->belongsTo(Duerp::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function dangers()
    {
        return $this->hasMany(DuerpDanger::class, 'work_unit_id');
    }
}
