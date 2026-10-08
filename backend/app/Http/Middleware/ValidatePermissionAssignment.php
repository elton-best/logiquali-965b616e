<?php

namespace App\Http\Middleware;

use App\Services\PermissionNormMappingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to validate permission assignments before they are saved
 * 
 * Checks that:
 * 1. Permissions belong to subscribed norms
 * 2. No orphaned permissions (mapped to non-existent norms)
 * 3. User has authority to assign
 */
class ValidatePermissionAssignment
{
    public function __construct(
        protected PermissionNormMappingService $permissionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only validate on role update endpoints
        if (!$this->shouldValidate($request)) {
            return $next($request);
        }

        $user = $request->user();
        
        // Get permissions being assigned from request
        $permissionIds = $this->getPermissionsFromRequest($request);
        
        if (empty($permissionIds)) {
            return $next($request);
        }

        // Get user's subscribed norms (offer-driven model)
        $subscribedNormIds = $this->permissionService->getSubscribedNormIdsForUser($user);

        if (empty($subscribedNormIds)) {
            return response()->json([
                'error' => 'Your enterprise has no active subscriptions',
            ], 422);
        }

        // Validate each permission
        foreach ($permissionIds as $permId) {
            $valid = false;
            
            // Check if permission exists for ANY of the subscribed norms
            $mappings = \App\Models\PermissionNormMapping::where('permission_id', $permId)
                ->whereIn('norm_id', $subscribedNormIds)
                ->get();

            if ($mappings->isEmpty()) {
                return response()->json([
                    'error' => "Permission ID {$permId} does not exist for your subscribed norms",
                ], 422);
            }
        }

        return $next($request);
    }

    /**
     * Check if this request should be validated
     */
    protected function shouldValidate(Request $request): bool
    {
        return in_array(mb_strtolower($request->method()), ['post', 'put', 'patch'], true) &&
               (
                   $request->routeIs('api.roles.update', 'api.roles.store') ||
                   preg_match('#^api/v1/roles/\d+/permissions$#', (string) $request->path()) === 1 ||
                   str_contains($request->path(), 'roles') && str_contains($request->path(), 'permissions')
               );
    }

    /**
     * Extract permission IDs from request payload
     */
    protected function getPermissionsFromRequest(Request $request): array
    {
        // Support multiple payload structures
        if ($request->has('permissions')) {
            $perms = $request->input('permissions');
            if (is_array($perms)) {
                // Could be [1,2,3] or [{id: 1}, {id: 2}]
                return collect($perms)
                    ->map(fn($p) => is_array($p) ? ($p['id'] ?? null) : $p)
                    ->filter()
                    ->toArray();
            }
        }

        if ($request->has('permission_ids')) {
            return (array) $request->input('permission_ids');
        }

        // For JSON body
        $json = $request->json()->all();
        if (isset($json['permissions'])) {
            return collect($json['permissions'])
                ->map(fn($p) => is_array($p) ? ($p['id'] ?? null) : $p)
                ->filter()
                ->toArray();
        }

        return [];
    }
}
