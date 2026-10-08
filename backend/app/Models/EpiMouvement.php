<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpiMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'epi_stock_id',
        'type',
        'quantite',
        'user_id',
        'motif',
        'observations',
        'date_mouvement',
    ];

    protected $casts = [
        'date_mouvement' => 'datetime',
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
