<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpPreventionAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['duerp_danger_id', 'category', 'action', 'responsible_id', 'deadline', 'status'];
    protected $casts = ['deadline' => 'date'];
    public function danger() { return $this->belongsTo(DuerpDanger::class, 'duerp_danger_id'); }
    public function responsible() { return $this->belongsTo(User::class, 'responsible_id'); }
}
