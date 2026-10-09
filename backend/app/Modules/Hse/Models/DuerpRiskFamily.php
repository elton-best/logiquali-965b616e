<?php

namespace App\Modules\Hse\Models;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpRiskFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'duerp_risk_families';

    protected $fillable = [
        'enterprise_id',
        'code',
        'name',
        'description',
        'is_active',
        'order_num',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function dangers()
    {
        return $this->hasMany(DuerpDanger::class, 'risk_family_id');
    }
}

