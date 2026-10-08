<?php

namespace App\Modules\Support\Models;

use App\Models\AspectEnvironnemental;
use App\Models\CodificationElement;
use App\Models\ConsommationEnergie;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\VerificationReglementaire;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Equipement extends Model
{
    use HasFactory, SoftDeletes, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'code_complet',
        'categorie_id',
        'localisation_id',
        'nom_commun',
        'nom_commun_abrege',
        'indice',
        'annee_acquisition',
        'marque',
        'modele',
        'numero_serie',
        'etat',
        'valeur_acquisition',
        'observations',
        'necessite_maintenance',
        'frequence_maintenance_jours',
        'actif',
    ];

    protected $casts = [
        'valeur_acquisition' => 'decimal:2',
        'necessite_maintenance' => 'boolean',
        'actif' => 'boolean',
        'epi_requis' => 'array',
        'habilitations_requises' => 'array',
        'impact_environnemental' => 'boolean',
        'usage_energetique_significatif' => 'boolean',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function categorie()
    {
        return $this->belongsTo(CodificationElement::class, 'categorie_id');
    }

    public function localisation()
    {
        return $this->belongsTo(CodificationElement::class, 'localisation_id');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function codeAliases()
    {
        return $this->hasMany(EquipementCodeAlias::class);
    }

    public function verificationsReglementaires()
    {
        return $this->hasMany(VerificationReglementaire::class);
    }

    public function aspectsEnvironnementaux()
    {
        return $this->hasMany(AspectEnvironnemental::class);
    }

    public function consommationsEnergie()
    {
        return $this->hasMany(ConsommationEnergie::class);
    }

    public function transferHistory()
    {
        return $this->hasMany(EquipementTransferHistory::class);
    }

    public static function deriveEnterpriseSigle(string $enterpriseName): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9 ]/', ' ', (string) Str::ascii($enterpriseName)) ?? '';
        $words = collect(explode(' ', trim($normalized)))
            ->filter(fn($word) => $word !== '')
            ->values();

        if ($words->count() >= 3) {
            return strtoupper($words->take(3)->map(fn($word) => mb_substr($word, 0, 1))->implode(''));
        }

        $joined = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $normalized) ?? '');
        if (mb_strlen($joined) >= 3) {
            return mb_substr($joined, 0, 3);
        }

        return str_pad($joined ?: 'ENT', 3, 'X');
    }

    public static function genererCodeComplet(
        string $enterpriseSigle,
        string $categorieCode,
        string $nomCommunAbrege,
        string $localisationCode,
        string $indice,
        int|string $annee
    ): string {
        return sprintf(
            '%s/%s/%s/%s/%s/%s',
            strtoupper($enterpriseSigle),
            strtoupper($categorieCode),
            strtoupper($nomCommunAbrege),
            strtoupper($localisationCode),
            strtoupper($indice),
            $annee
        );
    }

    public static function prochainIndice(string $nomCommunAbrege, ?int $enterpriseId = null): string
    {
        $query = self::query()->where('nom_commun_abrege', strtoupper($nomCommunAbrege));

        if ($enterpriseId) {
            $query->where('enterprise_id', $enterpriseId);
        }

        $dernier = $query
            ->orderBy('indice', 'desc')
            ->first();

        if (!$dernier) {
            return '001';
        }

        $prochainNumero = intval($dernier->indice) + 1;
        return str_pad($prochainNumero, 3, '0', STR_PAD_LEFT);
    }
}
