<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpWorkUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['enterprise_id', 'site_id', 'process_id', 'management_process_id', 'code', 'name', 'unit_type', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function families() { return $this->hasMany(DuerpRiskFamily::class, 'work_unit_id'); }
    public function process() { return $this->belongsTo(Process::class); }
    public function managementProcess() { return $this->belongsTo(Process::class, 'management_process_id'); }
}
