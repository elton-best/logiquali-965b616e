<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpeValeur extends Model
{
    use HasFactory;

    protected $table = 'ipe_valeurs';

    protected $fillable = [
        'ipe_id',
        'periode_debut',
        'periode_fin',
        'valeur',
        'ecart_reference',
        'ecart_objectif',
        'commentaire',
    ];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'valeur' => 'decimal:3',
        'ecart_reference' => 'decimal:3',
        'ecart_objectif' => 'decimal:3',
    ];

    public function ipe()
    {
        return $this->belongsTo(Ipe::class);
    }
}
