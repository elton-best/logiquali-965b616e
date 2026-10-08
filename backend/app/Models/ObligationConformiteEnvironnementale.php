<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObligationConformiteEnvironnementale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'type',
        'reference',
        'titre',
        'description',
        'autorite_competente',
        'date_application',
        'periodicite_controle',
        'date_prochain_controle',
        'statut_conformite',
        'preuves_conformite',
        'document_path',
        'actions_correctives',
    ];

    protected $casts = [
        'date_application' => 'date',
        'date_prochain_controle' => 'date',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeEcheanceProche($query, $days = 30)
    {
        return $query->where('date_prochain_controle', '<=', now()->addDays($days))
            ->where('date_prochain_controle', '>=', now());
    }

    public function scopeNonConforme($query)
    {
        return $query->where('statut_conformite', 'non_conforme');
    }

    public function isEchue(): bool
    {
        return $this->date_prochain_controle && $this->date_prochain_controle < now();
    }
}
