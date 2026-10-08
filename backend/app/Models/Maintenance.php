<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Maintenance extends Model
{
    use HasFactory, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'equipement_id',
        'type',
        'date_prevue',
        'date_realisee',
        'statut',
        'description',
        'responsable',
        'observations',
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'date_realisee' => 'date',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function suivis()
    {
        return $this->hasMany(MaintenanceSuivi::class);
    }

    public function getNiveauAlerteAttribute()
    {
        if ($this->statut !== 'planifie' || !$this->date_prevue) {
            return null;
        }

        $joursRestants = Carbon::now()->diffInDays($this->date_prevue, false);

        if ($joursRestants < 0) {
            return 'depassee';
        } elseif ($joursRestants === 0) {
            return 'aujourd_hui';
        } elseif ($joursRestants === 1) {
            return 'veille';
        } elseif ($joursRestants <= 3) {
            return 'trois_jours';
        } elseif ($joursRestants <= 7) {
            return 'sept_jours';
        }

        return null;
    }

    public function getSuiviActiveAttribute()
    {
        return Carbon::now()->greaterThanOrEqualTo($this->date_prevue);
    }

    public function scopeAvecAlertes($query)
    {
        return $query->where('statut', 'planifie')
            ->whereDate('date_prevue', '<=', Carbon::now()->addDays(7));
    }
}
