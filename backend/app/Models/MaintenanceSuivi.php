<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSuivi extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'user_id',
        'action',
        'date_action',
        'nouvelle_date_prevue',
        'commentaire',
        'preuve_path',
    ];

    protected $casts = [
        'date_action' => 'date',
        'nouvelle_date_prevue' => 'date',
    ];

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
