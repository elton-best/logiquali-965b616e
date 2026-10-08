<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\SecurityAuditLog;
use App\Services\PermissionScopeService;
use App\Services\Security\RoleScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly PermissionScopeService $permissionScopeService
    ) {}

    public function index(Request $request)
    {
        $rolesQuery = SpatieRole::query()->with('permissions');
        $user = $request->user();

        $this->roleScopeService->applyRoleVisibilityScopeForUser($rolesQuery, $user);

        $roles = $rolesQuery
            ->paginate(100);

        return RoleResource::collection($roles);
    }

    public function store(Request $request)
    {
        $this->guardCompanyRoleManagement($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($query) => $query->where('enterprise_id', (int) $request->input('enterprise_id'))),
            ],
            'enterprise_id' => 'required|integer|exists:enterprises,id',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable',
        ]);

        $this->guardRoleCreation($request, (string) $validated['name']);

        $role = SpatieRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'enterprise_id' => (int) $validated['enterprise_id'],
            'guard_name' => 'web',
        ]);
        $this->syncRoleEnterpriseScope($request, $role);
        $permissions = $this->filterRolePermissions(
            $this->resolvePermissions($request->input('permissions', []))
        );
        $this->assertPermissionsMatchActiveNorms($request, $permissions);
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return new RoleResource($role->load('permissions'));
    }

    public function show(Request $request, SpatieRole $role)
    {
        $user = $request->user()
            ?? auth('sanctum')->user()
            ?? auth()->user();

        $scopedQuery = SpatieRole::query()->whereKey($role->getKey());

        $this->roleScopeService->applyRoleVisibilityScopeForUser($scopedQuery, $user);

        $scopedRole = $scopedQuery->first();
        if (!$scopedRole) {
            $this->logRoleAccessDenied(
                $request,
                action: 'roles_scope_denied',
                role: $role,
                reason: 'role_outside_actor_scope',
                riskLevel: 'high'
            );
            abort(403, 'Acces refuse a ce role personnalise.');
        }

        return new RoleResource($scopedRole->load('permissions'));
    }

    public function update(Request $request, SpatieRole $role)
    {
        $this->guardCompanyRoleManagement($request, $role);
        $this->guardRoleScopeAccess($request, $role, true);
        $this->guardSystemRoleMutation($request, (string) $role->name);

        $enterpriseId = (int) ($request->input('enterprise_id') ?? $role->enterprise_id ?? 0);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where(fn ($query) => $query->where('enterprise_id', $enterpriseId)),
            ],
            'enterprise_id' => 'sometimes|integer|exists:enterprises,id',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable',
        ]);

        if (array_key_exists('name', $validated)) {
            $this->guardRoleCreation($request, (string) $validated['name']);
        }

        $role->update($validated);
        $this->syncRoleEnterpriseScope($request, $role);
        $permissions = $this->filterRolePermissions(
            $this->resolvePermissions($request->input('permissions', []))
        );
        $this->assertPermissionsMatchActiveNorms($request, $permissions);
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Request $request, SpatieRole $role)
    {
        $this->guardCompanyRoleManagement($request, $role);
        $this->guardRoleScopeAccess($request, $role, true);
        $this->guardSystemRoleMutation($request, (string) $role->name);
        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync role permissions via dedicated endpoint.
     */
    public function syncPermissions(Request $request, SpatieRole $role)
    {
        $this->guardCompanyRoleManagement($request, $role);
        $this->guardRoleScopeAccess($request, $role, true);
        $this->guardSystemRoleMutation($request, (string) $role->name);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'required',
        ]);

        $permissions = $this->filterRolePermissions(
            $this->resolvePermissions($validated['permissions'])
        );
        $this->assertPermissionsMatchActiveNorms($request, $permissions);

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permissions du rôle synchronisées avec succès.',
            'data' => new RoleResource($role->fresh()->load('permissions')),
        ]);
    }

    private function resolvePermissions(array $permissions)
    {
        if (empty($permissions)) {
            return [];
        }

        $ids = array_values(array_filter($permissions, fn ($value) => is_numeric($value)));
        if (!empty($ids)) {
            return SpatiePermission::query()
                ->whereIn('id', $ids)
                ->pluck('name')
                ->values()
                ->all();
        }

        $names = array_values(array_filter($permissions, fn ($value) => is_string($value) && trim($value) !== ''));
        if (!empty($names)) {
            $fetchedNames = SpatiePermission::query()
                ->whereIn('name', $names)
                ->pluck('name')
                ->values()
                ->all();

            $toAdd = [];
            foreach ($fetchedNames as $name) {
                if (preg_match('/^(.*)\.(create|update|delete|manage)$/', $name, $matches)) {
                    $toAdd[] = $matches[1] . '.read';
                }
            }

            if (!empty($toAdd)) {
                $readPerms = SpatiePermission::query()
                    ->whereIn('name', array_unique($toAdd))
                    ->pluck('name')
                    ->all();
                $fetchedNames = array_unique(array_merge($fetchedNames, $readPerms));
            }

            return $fetchedNames;
        }

        return [];
    }

    private function assertPermissionsMatchActiveNorms(Request $request, array $permissionNames): void
    {
        if (empty($permissionNames)) {
            return;
        }

        $actor = $request->user();
        if (!$actor || $actor->isSuperAdmin()) {
            return;
        }

        $siteId = $request->integer('site_id');
        if ($siteId <= 0) {
            $siteId = null;
        }

        $allowed = collect();
        if ($actor->isEnterpriseAdmin()) {
            $allowed = $this->permissionScopeService->getAssignablePermissionsForAdmin($actor);
        } elseif ($actor->isSiteManager() && $siteId) {
            $allowed = $this->permissionScopeService->getAccessiblePermissionsForSite($siteId);
        } elseif ($actor->isSiteManager() && $actor->site_id) {
            $allowed = $this->permissionScopeService->getAccessiblePermissionsForSite($actor->site_id);
        }

        $allowedNames = $allowed->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->flip();

        $invalid = collect($permissionNames)
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter(fn ($name) => !isset($allowedNames[$name]))
            ->values()
            ->all();

        if (!empty($invalid)) {
            abort(422, 'Permissions non éligibles pour les normes actives du site: ' . implode(', ', $invalid));
        }
    }

    private function filterRolePermissions(array $permissions): array
    {
        $blocked = collect((array) config('role_baselines.super_admin_only_permissions', []))
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => mb_strtolower(trim($permission)))
            ->flip();

        return collect($permissions)
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => mb_strtolower(trim($permission)))
            ->reject(fn ($permission) => isset($blocked[$permission]))
            ->unique()
            ->values()
            ->all();
    }

    private function guardRoleCreation(Request $request, string $roleName): void
    {
        if ($this->isProtectedRoleName($roleName)) {
            abort(422, 'Le nom de role est reserve.');
        }

        // Le scope de sécurité est porté par roles.enterprise_id (et non par le nom).
    }

    private function guardCompanyRoleManagement(Request $request, ?SpatieRole $role = null): void
    {
        $user = $request->user()
            ?? auth('sanctum')->user()
            ?? auth()->user();
        if (!$user || $user->isSuperAdmin()) {
            return;
        }

        if (!$user->isCompanyUser()) {
            return;
        }

        if ($user->isEnterpriseAdmin()) {
            return;
        }

        $this->logRoleAccessDenied(
            $request,
            action: 'roles_scope_denied',
            role: $role,
            reason: 'company_actor_requires_enterprise_admin',
            riskLevel: 'high'
        );
        abort(403, "Seul l'admin entreprise peut gerer les roles personnalises.");
    }

    private function guardSystemRoleMutation(Request $request, string $roleName): void
    {
        if ($this->isProtectedRoleName($roleName) && !($request->user()?->isSuperAdmin())) {
            $this->logRoleAccessDenied(
                $request,
                action: 'roles_mutation_denied',
                role: null,
                reason: 'protected_system_role',
                riskLevel: 'high',
                additionalMetadata: ['role_name' => $roleName]
            );
            abort(403, 'Ce role systeme est protege.');
        }
    }

    private function guardRoleScopeAccess(Request $request, SpatieRole $role, bool $isMutation = false): void
    {
        if (
            (int) ($role->enterprise_id ?? 0) <= 0
        ) {
            // Les rôles globaux (non scoppés entreprise) restent lisibles.
            if ($isMutation || $this->isMutatingRequest($request)) {
                $this->logRoleAccessDenied(
                    $request,
                    action: 'roles_scope_denied',
                    role: $role,
                    reason: 'legacy_custom_role_without_enterprise_scope',
                    riskLevel: 'high'
                );
                abort(403, 'Acces refuse a ce role.');
            }

            return;
        }

        $user = $request->user()
            ?? auth('sanctum')->user()
            ?? auth()->user();
        if (!$user) {
            $this->logRoleAccessDenied(
                $request,
                action: 'roles_scope_denied',
                role: $role,
                reason: 'unauthenticated_actor',
                riskLevel: 'medium'
            );
            abort(403, 'Acces refuse a ce role personnalise.');
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $enterpriseId = (int) ($user->enterprise_id ?? 0);
        if (!$this->roleBelongsToEnterprise($role, $enterpriseId)) {
            $this->logRoleAccessDenied(
                $request,
                action: 'roles_scope_denied',
                role: $role,
                reason: 'enterprise_scope_mismatch',
                riskLevel: 'high'
            );
            abort(403, 'Acces refuse a ce role personnalise.');
        }
    }

    private function logRoleAccessDenied(
        Request $request,
        string $action,
        ?SpatieRole $role,
        string $reason,
        string $riskLevel = 'medium',
        array $additionalMetadata = []
    ): void {
        $actor = $request->user()
            ?? auth('sanctum')->user()
            ?? auth()->user();

        $routeRole = $request->route('role');

        $resolvedRoleId = $role?->getKey();
        if (!$resolvedRoleId) {
            if ($routeRole instanceof SpatieRole) {
                $resolvedRoleId = $routeRole->getKey();
            } elseif (is_numeric($routeRole)) {
                $resolvedRoleId = (int) $routeRole;
            }
        }

        $resolvedRoleName = $role?->name;
        $resolvedRoleEnterpriseId = $role?->enterprise_id;

        if ($routeRole instanceof SpatieRole) {
            $resolvedRoleName ??= $routeRole->name;
            $resolvedRoleEnterpriseId ??= $routeRole->enterprise_id;
        }

        SecurityAuditLog::logEvent(
            eventType: 'authorization',
            action: $action,
            resourceType: SpatieRole::class,
            resourceId: is_numeric($resolvedRoleId) ? (int) $resolvedRoleId : null,
            metadata: array_merge([
                'reason' => $reason,
                'role_id' => is_numeric($resolvedRoleId) ? (int) $resolvedRoleId : null,
                'role_name' => $resolvedRoleName,
                'role_enterprise_id' => $resolvedRoleEnterpriseId ? (int) $resolvedRoleEnterpriseId : null,
                'actor_user_id' => $actor?->id,
                'actor_enterprise_id' => $actor?->enterprise_id ? (int) $actor->enterprise_id : null,
                'actor_site_id' => $actor?->site_id ? (int) $actor->site_id : null,
            ], $additionalMetadata),
            riskLevel: $riskLevel
        );
    }

    private function roleBelongsToEnterprise(SpatieRole $role, int $enterpriseId): bool
    {
        return $enterpriseId > 0
            && $this->roleScopeService->isRoleStrictlyScopedToEnterprise($role, $enterpriseId);
    }

    private function syncRoleEnterpriseScope(Request $request, SpatieRole $role): void
    {
        $resolvedEnterpriseId = (int) ($this->roleScopeService->resolveRoleEnterpriseScope(
            $request->user(),
            (string) $role->name
        ) ?? 0);
        $actor = $request->user();

        if ($actor?->isCompanyUser()) {
            $resolvedEnterpriseId = (int) ($actor->enterprise_id ?? 0);
        }

        $role->forceFill([
            'enterprise_id' => $resolvedEnterpriseId > 0 ? $resolvedEnterpriseId : null,
        ])->save();
    }

    private function isProtectedRoleName(string $name): bool
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return true;
        }

        if ($normalized === 'super_admin') {
            return true;
        }

        $baselineRoles = config('role_baselines.roles', []);
        if (is_array($baselineRoles) && array_key_exists($normalized, $baselineRoles)) {
            return true;
        }

        $catalogRoles = config('role_catalog.roles', []);
        if (is_array($catalogRoles) && array_key_exists($normalized, $catalogRoles)) {
            return true;
        }

        return false;
    }

    private function isMutatingRequest(Request $request): bool
    {
        return in_array(mb_strtoupper($request->getMethod()), ['PUT', 'PATCH', 'DELETE'], true);
    }
}
