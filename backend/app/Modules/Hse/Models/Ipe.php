<?php

namespace App\Modules\Hse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ipe';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'nom',
        'description',
        'formule_calcul',
        'unite',
        'valeur_reference',
        'objectif_cible',
        'date_reference',
        'periodicite',
        'actif',
    ];

    protected $casts = [
        'valeur_reference' => 'decimal:3',
        'objectif_cible' => 'decimal:3',
        'date_reference' => 'date',
        'actif' => 'boolean',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function valeurs()
    {
        return $this->hasMany(IpeValeur::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
