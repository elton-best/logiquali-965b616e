<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpiCatalogue extends Model
{
    use HasFactory;

    protected $table = 'epi_catalogue';

    protected $fillable = [
        'categorie',
        'designation',
        'reference_fabricant',
        'norme_ce',
        'description',
    ];

    public function stocks()
    {
        return $this->hasMany(EpiStock::class);
    }
}
