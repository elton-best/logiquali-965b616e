<?php

namespace App\Modules\Hse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationReglementaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipement_id',
        'type_verification',
        'organisme_agree',
        'numero_rapport',
        'date_verification',
        'date_prochaine_verification',
        'resultat',
        'observations',
        'reserves',
        'document_path',
        'cout',
    ];

    protected $casts = [
        'date_verification' => 'date',
        'date_prochaine_verification' => 'date',
        'cout' => 'decimal:2',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function scopeEcheanceProche($query, $days = 30)
    {
        return $query->where('date_prochaine_verification', '<=', now()->addDays($days))
            ->where('date_prochaine_verification', '>=', now());
    }

    public function scopeEchue($query)
    {
        return $query->where('date_prochaine_verification', '<', now());
    }

    public function isEchue(): bool
    {
        return $this->date_prochaine_verification < now();
    }

    public function daysUntilExpiration(): int
    {
        return now()->diffInDays($this->date_prochaine_verification, false);
    }
}
