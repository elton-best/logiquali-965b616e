<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpRiskFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['work_unit_id', 'name', 'description'];
    public function workUnit() { return $this->belongsTo(DuerpWorkUnit::class, 'work_unit_id'); }
    public function dangers() { return $this->hasMany(DuerpDanger::class, 'risk_family_id'); }
}
