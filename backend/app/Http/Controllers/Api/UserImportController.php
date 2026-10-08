<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserImportRequest;
use App\Jobs\ImportUsersJob;
use App\Models\Site;
use App\Models\User;
use App\Services\Security\RoleScopeService;
use App\Services\UploadSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserImportController extends Controller
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function import(UserImportRequest $request, UploadSecurityService $uploadSecurityService): JsonResponse
    {
        $this->authorize('create', User::class);
        $actor = $request->user();

        $uploadSecurityService->validateSpreadsheet($request->file('file'));

        $path = $request->file('file')->store('imports/users');

        $enterpriseId = $request->input('enterprise_id') ?: $request->user()->enterprise_id;
        if (!$actor->isSuperAdmin()) {
            $enterpriseId = (int) $actor->enterprise_id;
        }

        if (!$enterpriseId) {
            return response()->json([
                'success' => false,
                'message' => "Entreprise requise pour l'import.",
            ], 422);
        }

        $siteId = (int) $request->input('site_id');
        if ($this->isRestrictedSiteManager($actor)) {
            $siteId = (int) ($actor->site_id ?? 0);
        }

        if ($siteId <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Un site valide est requis pour l'import.",
            ], 422);
        }

        $site = Site::find($siteId);
        if (!$site || (int) $site->enterprise_id !== (int) $enterpriseId) {
            return response()->json([
                'success' => false,
                'message' => "Le site sélectionné n'appartient pas à l'entreprise courante.",
            ], 422);
        }

        // Pas de permissions automatiques par défaut (least privilege).
        $permissions = $this->resolveScopedImportPermissions((array) $request->input('permissions', []), $site);

        $requiresManualApproval = $this->requiresManualCollaboratorApproval($actor);
        $sendWelcome = !$requiresManualApproval && (bool) $request->input('send_welcome_email', true);
        $defaultRole = (string) $request->input('default_role', 'lecteur');
        if (!$this->isRoleAllowedForImport($defaultRole, (int) $enterpriseId)) {
            $defaultRole = 'lecteur';
        }
        $restrictPrivilegedRoles = $this->isRestrictedSiteManager($actor);
        if ($restrictPrivilegedRoles && $this->isForbiddenForRestrictedSiteManager($defaultRole)) {
            $defaultRole = 'lecteur';
        }
        if ($defaultRole === 'admin_entreprise') {
            $permissions = [];
        }

        ImportUsersJob::dispatch(
            $path,
            $enterpriseId,
            $siteId,
            $permissions,
            $sendWelcome,
            $defaultRole,
            $restrictPrivilegedRoles,
            $request->user()->id,
            $requiresManualApproval
        )
            ->onQueue('imports');

        $message = $requiresManualApproval
            ? "Import lancé. Les collaborateurs importés resteront en attente de validation par l'admin d'entreprise avant envoi des invitations."
            : 'Import lancé avec succès. Les utilisateurs seront créés en arrière-plan.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'job_reference' => Str::uuid()->toString(),
            'notifications' => [
                'approval_required' => $requiresManualApproval,
                'activation_email_sent' => !$requiresManualApproval && $sendWelcome,
            ],
        ], 202);
    }

    private function isRestrictedSiteManager(User $actor): bool
    {
        return $actor->user_type === 'company'
            && $actor->hasRole('site_manager')
            && !$actor->hasRole('admin_entreprise');
    }

    /**
     * @param array<int, mixed> $permissions
     * @return array<int, string>
     */
    private function resolveScopedImportPermissions(array $permissions, Site $site): array
    {
        $normalized = $this->normalizePermissionNames($permissions);
        if (empty($normalized)) {
            return [];
        }

        $activePrefixes = $this->activeCatalogPermissionPrefixesForSite($site);
        if ($activePrefixes->isEmpty()) {
            return [];
        }

        return collect($normalized)
            ->filter(fn(string $permission) => $this->matchesActivePrefix($permission, $activePrefixes))
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $permissions
     * @return array<int, string>
     */
    private function normalizePermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return trim($permission);
                }

                if (is_array($permission) && !empty($permission['name'])) {
                    return trim((string) $permission['name']);
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function matchesActivePrefix(string $permission, Collection $activePrefixes): bool
    {
        $candidate = mb_strtolower(trim($permission));
        if ($candidate === '') {
            return false;
        }

        foreach ($activePrefixes as $prefix) {
            if ($candidate === $prefix || str_starts_with($candidate, $prefix . '.')) {
                return true;
            }
        }

        return false;
    }

    private function activeCatalogPermissionPrefixesForSite(Site $site): Collection
    {
        $prefixes = collect();

        foreach ($site->getActiveSubscriptions() as $subscription) {
            foreach ($subscription->getAccessibleModules() as $module) {
                $moduleCode = mb_strtolower(trim((string) ($module->code ?? '')));
                if ($moduleCode !== '') {
                    $prefixes->push($moduleCode);
                }
            }

            foreach ($subscription->getAccessibleSubModules() as $subModule) {
                $moduleCode = mb_strtolower(trim((string) ($subModule->module?->code ?? '')));
                $subModuleCode = mb_strtolower(trim((string) ($subModule->code ?? '')));
                if ($moduleCode !== '' && $subModuleCode !== '') {
                    $prefixes->push($moduleCode . '.' . $subModuleCode);
                }
            }

            foreach ($subscription->getAccessibleSections() as $section) {
                $moduleCode = mb_strtolower(trim((string) ($section->subModule?->module?->code ?? '')));
                $subModuleCode = mb_strtolower(trim((string) ($section->subModule?->code ?? '')));
                $sectionCode = mb_strtolower(trim((string) ($section->code ?? '')));
                if ($moduleCode !== '' && $subModuleCode !== '' && $sectionCode !== '') {
                    $prefixes->push($moduleCode . '.' . $subModuleCode . '.' . $sectionCode);
                }
            }
        }

        return $prefixes->unique()->values();
    }

    private function isForbiddenForRestrictedSiteManager(string $roleName): bool
    {
        return in_array($roleName, ['admin_entreprise', 'site_manager'], true)
            || $this->isAnyCustomEnterpriseRole($roleName);
    }

    private function isRoleAllowedForImport(string $roleName, int $enterpriseId): bool
    {
        $catalogRoleNames = array_keys(config('role_catalog.roles', []));
        if (in_array($roleName, $catalogRoleNames, true)) {
            return true;
        }

        if ($enterpriseId <= 0) {
            return false;
        }

        $role = \Spatie\Permission\Models\Role::query()
            ->where('name', $roleName)
            ->first();
        if (!$role) {
            return false;
        }

        if ($this->roleScopeService->isLegacyCustomRoleWithoutEnterpriseScope($role, $roleName)) {
            return false;
        }

        if ($this->roleScopeService->isRoleStrictlyScopedToEnterprise($role, $enterpriseId)) {
            return true;
        }

        if ($this->roleScopeService->isAnyCustomEnterpriseRoleName($roleName)) {
            return false;
        }

        return false;
    }

    private function isAnyCustomEnterpriseRole(string $roleName): bool
    {
        $role = \Spatie\Permission\Models\Role::query()
            ->where('name', $roleName)
            ->first();
        return $role && (int) ($role->enterprise_id ?? 0) > 0;
    }

    private function requiresManualCollaboratorApproval(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return false;
        }

        return !$actor->isEnterpriseAdmin();
    }
}
