<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuerpRiskType extends Model
{
    use HasFactory;

    protected $fillable = ['enterprise_id', 'code', 'label', 'description', 'created_by', 'archived_at'];
    protected $casts = ['archived_at' => 'datetime'];
    public function dangers() { return $this->hasMany(DuerpDanger::class, 'risk_type_id'); }
}
