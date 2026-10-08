<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'enterprise_id',
        'site_id',
        'event_type',
        'resource_type',
        'resource_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'risk_level',
        'status'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function logEvent(
        string $eventType,
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = [],
        string $riskLevel = 'low'
    ): void {
        $user = auth()->user();
        $request = request();

        self::create([
            'user_id' => $user?->id,
            'enterprise_id' => $user?->enterprise_id,
            'site_id' => $user?->site_id,
            'event_type' => $eventType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
            'risk_level' => $riskLevel,
            'status' => 'logged'
        ]);
    }
}