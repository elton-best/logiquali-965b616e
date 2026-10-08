<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_version_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    // Action constants
    const ACTION_VIEW = 'view';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_PRINT = 'print';
    const ACTION_PREVIEW = 'preview';

    /**
     * Get the document version
     */
    public function documentVersion()
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: downloads only
     */
    public function scopeDownloads($query)
    {
        return $query->where('action', self::ACTION_DOWNLOAD);
    }

    /**
     * Scope: views only
     */
    public function scopeViews($query)
    {
        return $query->where('action', self::ACTION_VIEW);
    }

    /**
     * Scope: for specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: recent logs (last 30 days)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Log an access action
     */
    public static function logAccess(
        int $documentVersionId,
        int $userId,
        string $action,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'document_version_id' => $documentVersionId,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Get all valid actions
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_VIEW => 'Consultation',
            self::ACTION_DOWNLOAD => 'Téléchargement',
            self::ACTION_PRINT => 'Impression',
            self::ACTION_PREVIEW => 'Prévisualisation',
        ];
    }
}
