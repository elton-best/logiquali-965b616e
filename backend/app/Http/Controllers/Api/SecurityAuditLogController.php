<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;

class SecurityAuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:security_audit.read');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);
        $actor = $request->user();

        $query = $this->scopedQuery($request)
            ->with(['user', 'enterprise', 'site'])
            ->orderBy('created_at', 'desc');

        $this->applyFilters($query, $validated, true);

        $logs = $query->paginate((int) ($validated['per_page'] ?? 50));
        $logs->through(function (SecurityAuditLog $log) use ($actor): array {
            return $this->transformLogForActor($log, $actor);
        });

        return response()->json($logs);
    }

    public function stats(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);
        $query = $this->scopedQuery($request);

        $this->applyFilters($query, $validated, false);

        $stats = [
            'total_events' => (clone $query)->count(),
            'high_risk_events' => (clone $query)->where('risk_level', 'high')->count(),
            'critical_events' => (clone $query)->where('risk_level', 'critical')->count(),
            'events_today' => (clone $query)->whereDate('created_at', today())->count(),
            'failed_logins_today' => (clone $query)->where('action', 'post_failed')
                ->where('event_type', 'api_access')
                ->whereDate('created_at', today())
                ->count()
        ];

        return response()->json($stats);
    }

    public function shadowRbacSummary(Request $request): JsonResponse
    {
        $windowDays = max(1, min((int) $request->integer('window_days', 7), 90));

        $query = $this->scopedQuery($request)
            ->where('event_type', 'authorization')
            ->where('action', 'rbac_shadow_divergence')
            ->where('created_at', '>=', now()->subDays($windowDays));

        if ($request->filled('reason')) {
            $query->where('metadata->reason', (string) $request->string('reason'));
        }

        $rows = $query->get(['metadata', 'created_at']);
        $total = $rows->count();

        $byReason = ['permission' => 0, 'scope' => 0, 'subscription' => 0, 'other' => 0];
        $byPermission = [];
        $byDay = [];

        foreach ($rows as $row) {
            $metadata = is_array($row->metadata) ? $row->metadata : [];
            $reason = mb_strtolower(trim((string) ($metadata['reason'] ?? 'other')));
            $requestedPermission = mb_strtolower(trim((string) ($metadata['requested_permission'] ?? '')));
            $dayKey = $row->created_at?->format('Y-m-d') ?? today()->format('Y-m-d');

            if (!array_key_exists($reason, $byReason)) {
                $reason = 'other';
            }
            $byReason[$reason]++;

            if ($requestedPermission !== '') {
                $byPermission[$requestedPermission] = ($byPermission[$requestedPermission] ?? 0) + 1;
            }
            $byDay[$dayKey] = ($byDay[$dayKey] ?? 0) + 1;
        }

        arsort($byPermission);
        ksort($byDay);

        $topPermissions = collect($byPermission)
            ->take(10)
            ->map(fn ($count, $permission) => [
                'permission' => $permission,
                'count' => $count,
            ])
            ->values();

        $timeline = collect($byDay)
            ->map(fn ($count, $day) => [
                'day' => $day,
                'count' => $count,
            ])
            ->values();

        return response()->json([
            'window_days' => $windowDays,
            'total_divergences' => $total,
            'by_reason' => $byReason,
            'top_permissions' => $topPermissions,
            'timeline' => $timeline,
        ]);
    }

    private function scopedQuery(Request $request): Builder
    {
        $actor = $request->user();
        $query = SecurityAuditLog::query();

        if (!$actor instanceof User) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($actor->isSuperAdmin()) {
            if ($request->filled('enterprise_id')) {
                $query->where('enterprise_id', (int) $request->enterprise_id);
            }
            if ($request->filled('site_id')) {
                $query->where('site_id', (int) $request->site_id);
            }
            return $query;
        }

        if ($actor->isCompanyUser() && (int) $actor->enterprise_id > 0) {
            if (
                $request->filled('enterprise_id')
                && (int) $request->integer('enterprise_id') !== (int) $actor->enterprise_id
            ) {
                throw new AuthorizationException('Scope entreprise invalide pour cet utilisateur.');
            }

            $query->where('enterprise_id', (int) $actor->enterprise_id);

            if ($request->filled('site_id')) {
                $siteId = (int) $request->integer('site_id');

                if (
                    $actor->hasRole('site_manager')
                    && !$actor->hasRole('admin_entreprise')
                    && (int) ($actor->site_id ?? 0) > 0
                    && $siteId !== (int) $actor->site_id
                ) {
                    throw new AuthorizationException('Scope site invalide pour ce profil.');
                }

                if ($siteId > 0 && !$this->siteBelongsToEnterprise($siteId, (int) $actor->enterprise_id)) {
                    throw new AuthorizationException('Scope site invalide pour cette entreprise.');
                }

                $query->where('site_id', (int) $request->site_id);
            }

            if (
                $actor->hasRole('site_manager')
                && !$actor->hasRole('admin_entreprise')
                && (int) ($actor->site_id ?? 0) > 0
            ) {
                $query->where('site_id', (int) $actor->site_id);
            }

            return $query;
        }

        throw new AuthorizationException('Accès refusé: profil non autorisé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'event_type' => ['sometimes', 'string', 'max:80', 'regex:/^[A-Za-z0-9_:-]+$/'],
            'risk_level' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'critical'])],
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'action' => ['sometimes', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'site_id' => ['sometimes', 'integer', 'min:1'],
            'enterprise_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function applyFilters(Builder $query, array $validated, bool $allowUserFilter): void
    {
        if (!empty($validated['event_type'])) {
            $query->where('event_type', (string) $validated['event_type']);
        }

        if (!empty($validated['risk_level'])) {
            $query->where('risk_level', (string) $validated['risk_level']);
        }

        if ($allowUserFilter && !empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        if (!empty($validated['action'])) {
            $query->where('action', (string) $validated['action']);
        }

        if (!empty($validated['site_id'])) {
            $query->where('site_id', (int) $validated['site_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', (string) $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', (string) $validated['date_to']);
        }
    }

    private function siteBelongsToEnterprise(int $siteId, int $enterpriseId): bool
    {
        if ($siteId <= 0 || $enterpriseId <= 0) {
            return false;
        }

        return Site::query()
            ->whereKey($siteId)
            ->where('enterprise_id', $enterpriseId)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformLogForActor(SecurityAuditLog $log, ?User $actor): array
    {
        $payload = $log->toArray();

        if ($this->shouldMaskSensitiveFields($actor)) {
            $payload['ip_address'] = $this->maskIpAddress((string) ($payload['ip_address'] ?? ''));
            $payload['user_agent'] = $this->maskUserAgent((string) ($payload['user_agent'] ?? ''));
        }

        return $payload;
    }

    private function shouldMaskSensitiveFields(?User $actor): bool
    {
        return !$actor?->isSuperAdmin();
    }

    private function maskIpAddress(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '*';
                return implode('.', $parts);
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $visible = array_slice($parts, 0, 4);
            return implode(':', $visible) . ':****';
        }

        return 'masked';
    }

    private function maskUserAgent(string $userAgent): string
    {
        $value = trim($userAgent);
        if ($value === '') {
            return '';
        }

        if (mb_strlen($value) <= 24) {
            return $value;
        }

        return mb_substr($value, 0, 24) . '...';
    }
}
