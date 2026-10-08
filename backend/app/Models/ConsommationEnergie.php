<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsommationEnergie extends Model
{
    use HasFactory;

    protected $table = 'consommations_energie';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'equipement_id',
        'periode_debut',
        'periode_fin',
        'type_energie',
        'valeur_consommation',
        'unite',
        'mode_saisie',
        'source_donnee',
        'cout_euro',
        'emission_co2_kg',
        'observations',
    ];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'valeur_consommation' => 'decimal:3',
        'cout_euro' => 'decimal:2',
        'emission_co2_kg' => 'decimal:2',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('periode_debut', [$debut, $fin])
            ->orWhereBetween('periode_fin', [$debut, $fin]);
    }

    public function scopeTypeEnergie($query, $type)
    {
        return $query->where('type_energie', $type);
    }
}
