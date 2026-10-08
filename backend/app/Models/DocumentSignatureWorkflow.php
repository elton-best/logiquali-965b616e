<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentSignatureWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_id',
        'workflow_name',
        'total_steps',
        'current_step',
        'status',
        'initiated_by',
        'initiated_at',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'total_steps' => 'integer',
            'current_step' => 'integer',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Utilisateur ayant initié le workflow
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Toutes les signatures du workflow
     */
    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class, 'workflow_id')->orderBy('signature_order');
    }

    /**
     * Signature actuelle (en attente)
     */
    public function currentSignature()
    {
        return $this->hasOne(DocumentSignature::class, 'workflow_id')
            ->where('signature_order', $this->current_step)
            ->where('status', 'pending');
    }

    /**
     * Signatures complétées
     */
    public function completedSignatures()
    {
        return $this->signatures()->where('status', 'signed');
    }

    /**
     * Scope: Workflows en cours
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Workflows expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'in_progress')
            ->where('expires_at', '<', now());
    }

    /**
     * Obtenir le prochain signataire
     */
    public function nextSigner(): ?User
    {
        $nextSignature = $this->signatures()
            ->where('signature_order', $this->current_step)
            ->where('status', 'pending')
            ->first();

        return $nextSignature?->user;
    }

    /**
     * Vérifier si un utilisateur peut signer
     */
    public function canSign(User $user): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $currentSignature = $this->currentSignature;
        
        return $currentSignature && $currentSignature->user_id === $user->id;
    }

    /**
     * Avancer au prochain niveau
     */
    public function advance(): void
    {
        if ($this->current_step < $this->total_steps) {
            $this->increment('current_step');
            $this->update(['expires_at' => now()->addDays(7)]);
        } else {
            $this->complete();
        }
    }

    /**
     * Compléter le workflow
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Rejeter le workflow
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Expirer le workflow
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Vérifier si le workflow est expiré
     */
    public function isExpired(): bool
    {
        return $this->status === 'in_progress' && $this->expires_at->isPast();
    }

    /**
     * Validation: current_step <= total_steps
     */
    protected static function booted()
    {
        static::saving(function ($workflow) {
            if ($workflow->current_step > $workflow->total_steps) {
                throw new \InvalidArgumentException('current_step cannot exceed total_steps');
            }
        });
    }
}
