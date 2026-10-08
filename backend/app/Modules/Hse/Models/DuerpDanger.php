<?php

namespace App\Modules\Hse\Models;

use App\Models\Risk;

use App\Models\Process;
use App\Models\User;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpDanger extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'duerp_dangers';

    /**
     * Standard 14 INRS risk families from the official DUERP reference document.
     */
    public const INRS_FAMILIES = [
        'Risques de trébuchement, heurt ou autre chute de plain-pied',
        'Risques de chute de hauteur',
        'Risques liés aux circulations internes de véhicules',
        'Risques routiers en mission',
        'Risques liés à la charge physique (manutention, postures, TMS)',
        'Risques liés aux équipements de travail (machines, outils, écrans)',
        'Risques liés aux produits, émissions et déchets (chimiques/biologiques)',
        'Risques d\'incendie et d\'explosion',
        'Risques d\'origine électrique',
        'Risques liés au bruit et ambiances sonores',
        'Risques liés aux vibrations',
        'Risques liés aux rayonnements (ionisants, optiques, électromagnétiques)',
        'Risques liés aux ambiances lumineuses et thermiques (chaleur, froid)',
        'Risques psychosociaux (stress, agressions, charge mentale)',
    ];

    protected $fillable = [
        'duerp_id',
        'organizational_unit',
        'process_id',
        'work_unit',
        'activity',
        'inrs_family',
        'dangerous_situation',
        'identified_risks',
        'consequences',
        'gravity',
        'frequency',
        'raw_risk_score',
        'raw_risk_level',
        'existing_preventions',
        'prevention_actions',
        'responsible_id',
        'responsible_name',
        'deadline',
        'action_status', // en_continu, en_cours, realise
        'residual_gravity',
        'residual_frequency',
        'residual_risk_score',
        'residual_risk_level',
        'danger_type',
        'danger_description',
        'exposed_workers',
        'probability_score',
        'severity_score',
        'criticality_score',
        'criticality_level',
        'existing_measures',
        'actions',
        'applicable_norms',
    ];

    protected $casts = [
        'gravity' => 'integer',
        'frequency' => 'integer',
        'raw_risk_score' => 'integer',
        'residual_gravity' => 'integer',
        'residual_frequency' => 'integer',
        'residual_risk_score' => 'integer',
        'deadline' => 'date',
        'exposed_workers' => 'array',
        'actions' => 'array',
        'applicable_norms' => 'array',
        'probability_score' => 'integer',
        'severity_score' => 'integer',
        'criticality_score' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (DuerpDanger $danger) {
            // DUERP Canevas: Raw Risk Score = Gravity * Frequency (Scale 1..4)
            if ($danger->gravity !== null || $danger->frequency !== null) {
                $g = (int) ($danger->gravity ?? 1);
                $f = (int) ($danger->frequency ?? 1);
                $danger->raw_risk_score = $g * $f;
                $danger->raw_risk_level = match (true) {
                    $danger->raw_risk_score <= 3 => 'Faible',
                    $danger->raw_risk_score <= 8 => 'Moyen',
                    default => 'Significatif',
                };
            } elseif ($danger->probability_score !== null || $danger->severity_score !== null) {
                $prob = $danger->probability_score ?? 0;
                $sev = $danger->severity_score ?? 0;
                $danger->criticality_score = $prob * $sev;
                $danger->criticality_level = match (true) {
                    $danger->criticality_score <= 4 => 'acceptable',
                    $danger->criticality_score <= 8 => 'tolerable',
                    default => 'unacceptable',
                };
            }

            // Residual calculation if residual values provided
            if ($danger->residual_gravity !== null && $danger->residual_frequency !== null) {
                $rg = (int) $danger->residual_gravity;
                $rf = (int) $danger->residual_frequency;
                $danger->residual_risk_score = $rg * $rf;
                $danger->residual_risk_level = match (true) {
                    $danger->residual_risk_score <= 3 => 'Faible',
                    $danger->residual_risk_score <= 8 => 'Moyen',
                    default => 'Significatif',
                };
            }
        });
    }

    public function duerp()
    {
        return $this->belongsTo(Duerp::class, 'duerp_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}

