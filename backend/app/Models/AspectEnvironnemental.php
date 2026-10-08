<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AspectEnvironnemental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aspects_environnementaux';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'equipement_id',
        'process_id',
        'type',
        'designation',
        'description',
        'condition',
        'gravite',
        'frequence',
        'detectabilite',
        'mesures_maitrise',
        'objectifs_amelioration',
    ];

    protected $casts = [
        'aspect_significatif' => 'boolean',
    ];

    protected $appends = ['criticite'];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($aspect) {
            // Ne pas calculer si c'est une colonne générée
            // La base de données s'en charge
        });
    }

    public function getCriticiteAttribute()
    {
        // Si la colonne existe déjà (générée par la DB), la retourner
        if (isset($this->attributes['criticite'])) {
            return $this->attributes['criticite'];
        }
        
        // Sinon calculer pour les tests
        if ($this->gravite && $this->frequence && $this->detectabilite) {
            return $this->gravite * $this->frequence * $this->detectabilite;
        }
        
        return null;
    }

    public function getAspectSignificatifAttribute()
    {
        // Si la colonne existe déjà (générée par la DB), la retourner
        if (isset($this->attributes['aspect_significatif'])) {
            return (bool) $this->attributes['aspect_significatif'];
        }
        
        // Sinon calculer pour les tests
        $criticite = $this->getCriticiteAttribute();
        return $criticite !== null && $criticite >= 50;
    }

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

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function scopeSignificatifs($query)
    {
        // Utiliser la formule de calcul directement dans la requête
        return $query->whereRaw('(gravite * frequence * detectabilite) >= 50');
    }

    public function scopeCritiques($query, $seuil = 50)
    {
        return $query->where('criticite', '>=', $seuil);
    }
}
