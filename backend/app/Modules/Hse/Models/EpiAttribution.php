<?php

namespace App\Modules\Hse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpiAttribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'epi_stock_id',
        'user_id',
        'quantite_attribuee',
        'date_attribution',
        'date_renouvellement_prevue',
        'statut',
        'observations',
    ];

    protected $casts = [
        'date_attribution' => 'date',
        'date_renouvellement_prevue' => 'date',
    ];

    public function epiStock()
    {
        return $this->belongsTo(EpiStock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
