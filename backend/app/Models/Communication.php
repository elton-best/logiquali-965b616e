<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Communication extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'numero',
        'type',
        'designation',
        'cibles',
        'moyens',
        'chronogramme',
        'responsable',
        'organizer_user_id',
        'responsible_user_id',
        'participant_user_ids',
        'process_id',
        'cout',
        'date_debut',
        'date_fin',
        'period_mode',
        'plan_year',
        'status',
        'frequency',
        'observations',
        'site_id',
        'created_by',
    ];

    protected $casts = [
        'cibles' => 'array',
        'moyens' => 'array',
        'chronogramme' => 'array',
        'participant_user_ids' => 'array',
        'cout' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'plan_year' => 'integer',
    ];

    protected $with = ['proofs', 'history', 'alerts'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /** Organisateur interne — responsable de l'organisation */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    /** Chargé de com/sensibilisation interne — null si externe */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(CommunicationProof::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(CommunicationHistory::class)->orderBy('created_at', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(CommunicationAlert::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($communication) {
            if (!$communication->numero) {
                $communication->numero = static::max('numero') + 1;
            }
            // Backward-compatible default for legacy flows/tests that do not pass organizer explicitly.
            if (!$communication->organizer_user_id && $communication->created_by) {
                $communication->organizer_user_id = $communication->created_by;
            }
        });
    }
}
