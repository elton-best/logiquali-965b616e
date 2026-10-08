<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EvaluationRequest extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, LogsActivity, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'token',
        'enterprise_id',
        'site_id',
        'type',
        'recipient_email',
        'recipient_name',
        'recipient_company',
        'subject',
        'message',
        'sent_at',
        'opened_at',
        'responded_at',
        'expires_at',
        'status',
        'reminder_count',
        'last_reminder_at',
        'requestable_type',
        'requestable_id',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'metadata' => 'array',
        'reminder_count' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'type' => 'satisfaction_client',
        'reminder_count' => 0,
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = (string) Str::uuid();
            }
        });
    }

    /**
     * Préfixe pour la référence auto-générée
     */
    public function getReferencePrefix(): string
    {
        return 'EVAL-REQ';
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'recipient_email', 'sent_at', 'responded_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Entité liée (audit, etc.)
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Derniere réponse reçue (compatibilite UI existante)
     */
    public function response(): HasOne
    {
        return $this->hasOne(EvaluationResponse::class)->latestOfMany();
    }

    /**
     * Historique des réponses reçues
     */
    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class)
            ->latest('created_at');
    }

    /**
     * Criteres figes pour cette demande d'evaluation
     */
    public function criteria(): BelongsToMany
    {
        return $this->belongsToMany(
            EvaluationCriteria::class,
            'evaluation_request_criteria',
            'evaluation_request_id',
            'evaluation_criteria_id'
        )
            ->withPivot([
                'criterion_name',
                'criterion_code',
                'criterion_description',
                'criterion_category',
                'scale_type',
                'scale_min',
                'scale_max',
                'scale_labels',
                'weight',
                'is_mandatory',
                'display_order',
            ])
            ->withTimestamps()
            ->orderBy('evaluation_request_criteria.display_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending']);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAwaitingResponse($query)
    {
        return $query->whereIn('status', ['sent', 'opened']);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Générer l'URL publique du formulaire
     */
    public function getPublicUrl(): string
    {
        return url("/evaluation/{$this->token}");
    }

    /**
     * Marquer comme envoyé
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Marquer comme ouvert
     */
    public function markAsOpened(): void
    {
        if ($this->status === 'sent') {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'responded_at' => now(),
        ]);
    }

    /**
     * Marquer comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Annuler la demande
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Incrémenter le compteur de relances
     */
    public function incrementReminderCount(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
    }

    /**
     * Vérifier si la demande est expirée
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si la demande peut recevoir une réponse
     */
    public function canRespond(): bool
    {
        return !in_array($this->status, ['cancelled'], true) && !$this->isExpired();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'sent' => 'Envoyé',
            'opened' => 'Ouvert',
            'completed' => 'Complété',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'satisfaction_client' => 'Satisfaction client',
            'satisfaction_personnel' => 'Satisfaction personnel',
            'performance_personnel' => 'Performance personnel',
            'evaluation_personnel' => 'Évaluation personnel',
            'evaluation_auditeur' => 'Évaluation auditeur',
            'satisfaction_fournisseur' => 'Satisfaction prestataire',
            'performance_fournisseur' => 'Performance prestataire',
            'evaluation_fournisseur' => 'Évaluation fournisseur',
            'audit_interne' => 'Audit interne',
            default => $this->type,
        };
    }
}
