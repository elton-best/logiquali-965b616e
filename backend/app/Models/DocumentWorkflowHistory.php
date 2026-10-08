<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'document_workflow_history';

    protected $fillable = [
        'document_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'metadata',
        'delegated_to',
        'action_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('action_at', '>=', now()->subDays($days));
    }

    public function scopeWithRelations($query)
    {
        return $query->with(['user', 'delegatedTo']);
    }

    /**
     * Get action label in French
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'submitted' => 'Soumis pour vérification',
            'verified' => 'Vérifié',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'rejection_confirmed' => 'Rejet confirmé',
            'rejection_cancelled' => 'Rejet annulé',
            'delegated' => 'Délégué',
            'reminded' => 'Rappel envoyé',
            'generated' => 'Généré',
            'regenerated' => 'Régénéré',
            'downloaded' => 'Téléchargé',
            'previewed' => 'Prévisualisé',
            default => $this->action,
        };
    }

    /**
     * Check if action requires comment
     */
    public static function requiresComment(string $action): bool
    {
        return in_array($action, ['rejected', 'delegated']);
    }

    /**
     * Create workflow history entry
     */
    public static function logAction(
        int $documentId,
        int $userId,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $comment = null,
        ?array $metadata = null,
        ?int $delegatedTo = null
    ): self {
        return self::create([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'metadata' => $metadata,
            'delegated_to' => $delegatedTo,
            'action_at' => now(),
        ]);
    }
}
