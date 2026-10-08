<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_version_id',
        'step_order',
        'role_required',
        'approver_id',
        'status',
        'comments',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CHANGES_REQUESTED = 'changes_requested';

    /**
     * Get the document version
     */
    public function documentVersion()
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scope: pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: for specific role
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where('role_required', $role);
    }

    /**
     * Mark as approved
     */
    public function approve(int $userId, ?string $comments = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approver_id' => $userId,
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as rejected
     */
    public function reject(int $userId, ?string $comments = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approver_id' => $userId,
            'comments' => $comments,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Request changes
     */
    public function requestChanges(int $userId, string $comments): void
    {
        $this->update([
            'status' => self::STATUS_CHANGES_REQUESTED,
            'approver_id' => $userId,
            'comments' => $comments,
        ]);
    }

    /**
     * Check if approval is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    /**
     * Get all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_CHANGES_REQUESTED => 'Modifications demandées',
        ];
    }
}
