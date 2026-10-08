<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodificationElement extends Model
{
    use HasFactory, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'type',
        'code',
        'libelle',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function equipementsCategorie()
    {
        return $this->hasMany(Equipement::class, 'categorie_id');
    }

    public function equipementsLocalisation()
    {
        return $this->hasMany(Equipement::class, 'localisation_id');
    }

    public function scopeCategories($query)
    {
        return $query->where('type', 'categorie')->where('actif', true);
    }

    public function scopeLocalisations($query)
    {
        return $query->where('type', 'localisation')->where('actif', true);
    }
}
