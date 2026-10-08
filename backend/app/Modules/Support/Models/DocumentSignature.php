<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class DocumentSignature extends Model
{
    protected $fillable = [
        'workflow_id',
        'document_type',
        'document_id',
        'user_id',
        'signature_order',
        'role_required',
        'signature_text',
        'signed_at',
        'status',
        'comments',
        'ip_address',
        'user_agent',
        'document_hash',
        'password_verified',
        'is_revoked',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'rejection_reason',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'revoked_at' => 'datetime',
        'password_verified' => 'boolean',
        'is_revoked' => 'boolean',
        'signature_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Workflow parent (si signature fait partie d'un workflow)
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentSignatureWorkflow::class, 'workflow_id');
    }

    /**
     * Scope pour signatures actives (non révoquées)
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }

    /**
     * Scope pour un type de document
     */
    public function scopeForDocument($query, string $type, int $id)
    {
        return $query->where('document_type', $type)
                     ->where('document_id', $id);
    }

    /**
     * Scope: Signatures en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Signatures complétées
     */
    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    /**
     * Signer le document
     */
    public function sign(string $password): void
    {
        if (!Hash::check($password, $this->user->password)) {
            throw new \InvalidArgumentException('Invalid password');
        }

        $this->update([
            'status' => 'signed',
            'signed_at' => now(),
            'password_verified' => true,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Avancer le workflow si applicable
        if ($this->workflow) {
            $this->workflow->advance();
        }
    }

    /**
     * Rejeter la signature
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Rejeter le workflow si applicable
        if ($this->workflow) {
            $this->workflow->reject();
        }
    }
}
