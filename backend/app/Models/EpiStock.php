<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpiStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'epi_catalogue_id',
        'taille',
        'quantite_stock',
        'seuil_alerte',
        'prix_unitaire',
        'emplacement_stockage',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function epiCatalogue()
    {
        return $this->belongsTo(EpiCatalogue::class);
    }

    public function catalogue()
    {
        return $this->belongsTo(EpiCatalogue::class, 'epi_catalogue_id');
    }

    public function mouvements()
    {
        return $this->hasMany(EpiMouvement::class);
    }

    public function attributions()
    {
        return $this->hasMany(EpiAttribution::class);
    }

    public function scopeAlerteSeuil($query)
    {
        return $query->whereColumn('quantite_stock', '<=', 'seuil_alerte');
    }

    public function isAlerteSeuil(): bool
    {
        return $this->quantite_stock <= $this->seuil_alerte;
    }
}
