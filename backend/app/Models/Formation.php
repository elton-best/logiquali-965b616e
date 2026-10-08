<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formation extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'numero',
        'designation',
        'cibles',
        'target_user_ids',
        'chronogramme',
        'formateur',
        'formateur_user_id',
        'organizer_user_id',
        'process_id',
        'cout',
        'date_debut',
        'date_fin',
        'plan_year',
        'period_mode',
        'period_label',
        'status',
        'frequency',
        'observations',
        'site_id',
        'created_by',
        'generates_habilitation_type',
        'habilitation_validity_months',
        'issuing_authority',
    ];

    protected $casts = [
        'cibles' => 'array',
        'target_user_ids' => 'array',
        'chronogramme' => 'array',
        'cout' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'plan_year' => 'integer',
    ];

    protected $with = ['proofs', 'history', 'alerts', 'internalEvaluation'];

    public const STATUS_PLANIFIEE = 'planifiee';
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_REALISEE = 'realisee';
    public const STATUS_REPLANIFIEE = 'replanifiee';
    public const STATUS_ANNULEE = 'annulee';

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /** Organisateur interne — responsable de la formation */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    /** Formateur interne — null si formateur externe */
    public function formateurUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(FormationProof::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(FormationHistory::class)->orderBy('created_at', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(FormationAlert::class);
    }

    public function internalEvaluation(): HasOne
    {
        return $this->hasOne(FormationInternalEvaluation::class);
    }

    public function generateHabilitation(User $user): ?Habilitation
    {
        // Only generate if formation is configured to do so
        if (!$this->generates_habilitation_type || !$this->habilitation_validity_months) {
            return null;
        }

        // Check if formation is completed
        if ($this->status !== self::STATUS_REALISEE) {
            return null;
        }

        return Habilitation::create([
            'user_id' => $user->id,
            'type' => $this->generates_habilitation_type,
            'title' => "Habilitation {$this->generates_habilitation_type} - {$this->designation}",
            'description' => "Habilitation générée suite à la formation: {$this->designation}",
            'certificate_number' => 'FORM-' . $this->numero . '-' . $user->id . '-' . now()->format('Ymd'),
            'issued_date' => now(),
            'expiry_date' => now()->addMonths($this->habilitation_validity_months),
            'issuing_authority' => $this->issuing_authority ?? $this->formateur,
            'status' => 'active',
            'notes' => "Générée automatiquement depuis la formation #{$this->numero}"
        ]);
    }

    public function isQualifying(): bool
    {
        return !empty($this->generates_habilitation_type);
    }

    public function isOverdue(): bool
    {
        $effectiveStatus = $this->status ?: self::STATUS_PLANIFIEE;

        return in_array($effectiveStatus, [self::STATUS_PLANIFIEE, self::STATUS_REPLANIFIEE], true)
            && $this->date_fin
            && $this->date_fin->lt(now()->startOfDay());
    }

    public function runtimeStatus(): string
    {
        $effectiveStatus = $this->status ?: self::STATUS_PLANIFIEE;

        return $this->isOverdue() ? self::STATUS_EN_ATTENTE : $effectiveStatus;
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->runtimeStatus(), [self::STATUS_PLANIFIEE, self::STATUS_REPLANIFIEE, self::STATUS_EN_ATTENTE], true);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->runtimeStatus(), [self::STATUS_PLANIFIEE, self::STATUS_REPLANIFIEE, self::STATUS_EN_ATTENTE], true);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [self::STATUS_REALISEE, self::STATUS_ANNULEE], true);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($formation) {
            if (!$formation->numero) {
                $formation->numero = static::max('numero') + 1;
            }
            // Backward-compatible default for legacy flows/tests that do not pass organizer explicitly.
            if (!$formation->organizer_user_id && $formation->created_by) {
                $formation->organizer_user_id = $formation->created_by;
            }
        });
    }
}
