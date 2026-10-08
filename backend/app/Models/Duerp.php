<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Duerp extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'version',
        'is_current',
        'evaluation_date',
        'next_evaluation_date',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'evaluation_date' => 'date',
        'next_evaluation_date' => 'date',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function dangers()
    {
        return $this->hasMany(DuerpDanger::class, 'duerp_id');
    }

}
