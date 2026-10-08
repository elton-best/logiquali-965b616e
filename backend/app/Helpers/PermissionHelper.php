<?php

namespace App\Helpers;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Helper pour vérifier les permissions
 * Implémente la logique: entreprise = admin complet
 */
class PermissionHelper
{
    /**
     * Track déjà journalisés pour éviter le bruit.
     *
     * @var array<string, bool>
     */
    private static array $loggedLegacyAliases = [];
    private static array $activeScopedPermissionsCache = [];

    /**
     * Vérifie si l'utilisateur a l'autorisation
     * 
     * @param User $user
     * @param string $permission Nom de la permission (ex: 'processes.create')
     * @param callable|null $additionalCheck Vérification supplémentaire
     * @return bool
     */
    public static function can(User $user, string $permission, ?callable $additionalCheck = null): bool
    {
        // 1. Super admin peut TOUT
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Vérifier les permissions Spatie (moteur courant).
        $effectiveCandidates = self::permissionCandidates($permission);
        $grantedByPermission = self::hasAnyPermission($user, $effectiveCandidates);
        $grantedByActiveScope = null;
        if ($grantedByPermission && self::activeScopeEnforced($user) && !$user->isSuperAdmin()) {
            $scopeCandidates = array_values(array_unique(array_merge(
                $effectiveCandidates,
                self::canonicalCandidates($permission),
            )));
            $grantedByActiveScope = self::hasAnyActiveScopedPermission($user, $scopeCandidates);
        }
        $additionalCheckResult = null;
        if ($additionalCheck && is_callable($additionalCheck)) {
            $additionalCheckResult = (bool) $additionalCheck($user);
        }

        if (self::shadowComparisonEnabled()) {
            self::logShadowDivergenceIfAny(
                user: $user,
                requestedPermission: $permission,
                canonicalCandidates: self::canonicalCandidates($permission),
                legacyCandidates: self::legacyCandidates($permission),
                grantedByPermission: $grantedByPermission,
                additionalCheckResult: $additionalCheckResult,
            );
        }

        if ($grantedByPermission) {
            if ($grantedByActiveScope === false) {
                return false;
            }

            return true;
        }

        // 3. Vérification additionnelle (optionnelle)
        if ($additionalCheckResult !== null) {
            return $additionalCheckResult;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut consulter (view)
     */
    public static function canView(User $user, string $module): bool
    {
        return self::can($user, self::buildPermission($module, 'read'));
    }

    /**
     * Vérifie si l'utilisateur peut créer (create)
     */
    public static function canCreate(User $user, string $module): bool
    {
        return self::can($user, self::buildPermission($module, 'create'));
    }

    /**
     * Vérifie si l'utilisateur peut modifier (edit)
     */
    public static function canEdit(User $user, string $module): bool
    {
        return self::can($user, self::buildPermission($module, 'update'));
    }

    /**
     * Vérifie si l'utilisateur peut supprimer (delete)
     */
    public static function canDelete(User $user, string $module): bool
    {
        return self::can($user, self::buildPermission($module, 'delete'));
    }

    /**
     * Vérifie une permission ET un contrôle de scope (si fourni).
     * Contrairement à can(), le callback ne donne jamais un accès en bypass.
     */
    public static function canWithinScope(User $user, string $permission, ?callable $scopeCheck = null): bool
    {
        if (!self::can($user, $permission)) {
            return false;
        }

        if ($scopeCheck && is_callable($scopeCheck)) {
            return (bool) $scopeCheck($user);
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur est admin (entreprise ou super_admin)
     */
    public static function isAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isEnterpriseAdmin();
    }

    /**
     * Vérifie si l'utilisateur est collaborateur (clienta)
     */
    public static function isCollaborator(User $user): bool
    {
        return $user->isCompanyUser() && !$user->isEnterpriseAdmin();
    }

    private static function buildPermission(string $module, string $action): string
    {
        return self::normalizeModuleAlias($module) . '.' . self::normalizeAction($action);
    }

    /**
     * Build permission candidates (canonical only in strict mode).
     */
    private static function permissionCandidates(string $permission): array
    {
        $normalizedPermission = self::normalizePermissionAlias($permission);
        $index = strrpos($normalizedPermission, '.');
        if ($index === false) {
            if (self::strictLegacyAliasesEnabled()) {
                return [$normalizedPermission];
            }

            return array_values(array_unique([$permission, $normalizedPermission]));
        }

        $module = substr($normalizedPermission, 0, $index);
        $action = substr($normalizedPermission, $index + 1);

        $normalizedModule = self::normalizeModuleAlias($module);
        $normalizedAction = self::normalizeAction($action);
        $aliasUsed = $module !== $normalizedModule;
        $canonicalPermission = $normalizedModule . '.' . $normalizedAction;

        if ($aliasUsed) {
            self::logLegacyAliasUsage($module, $normalizedModule, $normalizedPermission);
        }

        if (self::strictLegacyAliasesEnabled()) {
            return array_values(array_unique([$canonicalPermission]));
        }

        return array_values(array_unique([
            $permission,
            $normalizedPermission,
            $module . '.' . $normalizedAction,
            $normalizedModule . '.' . $action,
            $canonicalPermission,
        ]));
    }

    private static function normalizePermissionAlias(string $permission): string
    {
        return mb_strtolower(trim($permission));
    }

    private static function normalizeModuleAlias(string $module): string
    {
        $aliases = config('authz.legacy_module_aliases', []);
        if (!is_array($aliases)) {
            return $module;
        }

        return $aliases[$module] ?? $module;
    }

    private static function normalizeAction(string $action): string
    {
        return $action;
    }

    private static function hasAnyPermission(User $user, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            try {
                if ($user->hasPermissionTo($candidate)) {
                    return true;
                }
            } catch (\Throwable) {
                // Permission absente du catalogue: ignorer le candidat.
            }
        }

        return false;
    }

    private static function canonicalCandidates(string $permission): array
    {
        $normalizedPermission = self::normalizePermissionAlias($permission);
        $index = strrpos($normalizedPermission, '.');
        if ($index === false) {
            return [$normalizedPermission];
        }

        $module = substr($normalizedPermission, 0, $index);
        $action = substr($normalizedPermission, $index + 1);

        return [self::normalizeModuleAlias($module) . '.' . self::normalizeAction($action)];
    }

    private static function legacyCandidates(string $permission): array
    {
        $rawPermission = mb_strtolower(trim($permission));
        $index = strrpos($rawPermission, '.');
        if ($index === false) {
            return [$rawPermission];
        }

        $module = substr($rawPermission, 0, $index);
        $action = substr($rawPermission, $index + 1);
        $normalizedModule = self::normalizeModuleAlias($module);
        $normalizedAction = self::normalizeAction($action);

        return array_values(array_unique([
            $rawPermission,
            $module . '.' . $normalizedAction,
            $normalizedModule . '.' . $action,
            $normalizedModule . '.' . $normalizedAction,
        ]));
    }

    private static function shadowComparisonEnabled(): bool
    {
        return (bool) config('authz.shadow_compare_enabled', false);
    }

    private static function logShadowDivergenceIfAny(
        User $user,
        string $requestedPermission,
        array $canonicalCandidates,
        array $legacyCandidates,
        bool $grantedByPermission,
        ?bool $additionalCheckResult,
    ): void {
        $canonicalGranted = self::hasAnyPermission($user, $canonicalCandidates);
        $legacyGranted = self::hasAnyPermission($user, $legacyCandidates);

        if ($canonicalGranted !== $legacyGranted) {
            self::logShadowEvent(
                user: $user,
                requestedPermission: $requestedPermission,
                reason: 'permission',
                legacyCandidates: $legacyCandidates,
                canonicalCandidates: $canonicalCandidates,
                legacyGranted: $legacyGranted,
                canonicalGranted: $canonicalGranted,
            );
        }

        if (self::shadowCompareIncludeScope() && $grantedByPermission && $additionalCheckResult === false) {
            self::logShadowEvent(
                user: $user,
                requestedPermission: $requestedPermission,
                reason: 'scope',
                legacyCandidates: $legacyCandidates,
                canonicalCandidates: $canonicalCandidates,
                legacyGranted: true,
                canonicalGranted: false,
            );
        }

        if (self::shadowCompareIncludeSubscription() && $grantedByPermission && !$user->isSuperAdmin()) {
            $activeScopedSet = self::getActiveScopedPermissionSet($user);
            if (!empty($activeScopedSet)) {
                $subscriptionCandidates = array_values(array_unique(array_merge($legacyCandidates, $canonicalCandidates)));
                $subscriptionGranted = false;
                foreach ($subscriptionCandidates as $candidate) {
                    if (isset($activeScopedSet[mb_strtolower(trim($candidate))])) {
                        $subscriptionGranted = true;
                        break;
                    }
                }

                if (!$subscriptionGranted) {
                    self::logShadowEvent(
                        user: $user,
                        requestedPermission: $requestedPermission,
                        reason: 'subscription',
                        legacyCandidates: $legacyCandidates,
                        canonicalCandidates: $canonicalCandidates,
                        legacyGranted: true,
                        canonicalGranted: false,
                    );
                }
            }
        }
    }

    private static function getActiveScopedPermissionSet(User $user): array
    {
        $key = (int) $user->id;
        if (isset(self::$activeScopedPermissionsCache[$key])) {
            return self::$activeScopedPermissionsCache[$key];
        }

        if (!$user->isCompanyUser()) {
            self::$activeScopedPermissionsCache[$key] = [];
            return self::$activeScopedPermissionsCache[$key];
        }

        $set = [];
        foreach ($user->getActiveScopedPermissionNames()->all() as $permissionName) {
            $normalized = mb_strtolower(trim((string) $permissionName));
            if ($normalized !== '') {
                $set[$normalized] = true;
            }
        }

        self::$activeScopedPermissionsCache[$key] = $set;
        return self::$activeScopedPermissionsCache[$key];
    }

    private static function hasAnyActiveScopedPermission(User $user, array $candidates): bool
    {
        if (!$user->isCompanyUser()) {
            return true;
        }

        $activeScopedSet = self::getActiveScopedPermissionSet($user);
        if (empty($activeScopedSet)) {
            return false;
        }

        foreach ($candidates as $candidate) {
            $normalized = mb_strtolower(trim((string) $candidate));
            if ($normalized !== '' && isset($activeScopedSet[$normalized])) {
                return true;
            }
        }

        return false;
    }

    private static function shadowCompareIncludeScope(): bool
    {
        return (bool) config('authz.shadow_compare_include_scope', true);
    }

    private static function shadowCompareIncludeSubscription(): bool
    {
        return (bool) config('authz.shadow_compare_include_subscription', true);
    }

    private static function logShadowEvent(
        User $user,
        string $requestedPermission,
        string $reason,
        array $legacyCandidates,
        array $canonicalCandidates,
        bool $legacyGranted,
        bool $canonicalGranted,
    ): void {
        $key = "shadow|{$reason}|{$user->id}|{$requestedPermission}|{$legacyGranted}|{$canonicalGranted}";
        if (isset(self::$loggedLegacyAliases[$key])) {
            return;
        }
        self::$loggedLegacyAliases[$key] = true;

        Log::warning('RBAC shadow divergence detected', [
            'reason' => $reason,
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'requested_permission' => $requestedPermission,
            'legacy_candidates' => $legacyCandidates,
            'canonical_candidates' => $canonicalCandidates,
            'legacy_granted' => $legacyGranted,
            'canonical_granted' => $canonicalGranted,
            'strict_mode' => self::strictLegacyAliasesEnabled(),
        ]);

        SecurityAuditLog::logEvent(
            eventType: 'authorization',
            action: 'rbac_shadow_divergence',
            resourceType: 'permission',
            metadata: [
                'reason' => $reason,
                'requested_permission' => $requestedPermission,
                'legacy_candidates' => $legacyCandidates,
                'canonical_candidates' => $canonicalCandidates,
                'legacy_granted' => $legacyGranted,
                'canonical_granted' => $canonicalGranted,
                'strict_mode' => self::strictLegacyAliasesEnabled(),
                'request_path' => request()?->path(),
                'request_method' => request()?->method(),
            ],
            riskLevel: $reason === 'permission' ? 'high' : 'medium',
        );
    }

    private static function strictLegacyAliasesEnabled(): bool
    {
        return (bool) config('authz.strict_legacy_aliases', true);
    }

    public static function isActiveScopeEnforcedForUser(User $user): bool
    {
        return self::activeScopeEnforced($user);
    }

    private static function activeScopeEnforced(User $user): bool
    {
        if ((bool) config('authz.enforce_active_scope', false)) {
            return true;
        }

        $cohortUserIds = config('authz.enforce_active_scope_cohort_user_ids', []);
        if (is_array($cohortUserIds) && $cohortUserIds !== [] && in_array((int) $user->id, $cohortUserIds, true)) {
            return true;
        }

        $cohortEnterpriseIds = config('authz.enforce_active_scope_cohort_enterprise_ids', []);
        if (
            is_array($cohortEnterpriseIds)
            && $cohortEnterpriseIds !== []
            && in_array((int) ($user->enterprise_id ?? 0), $cohortEnterpriseIds, true)
        ) {
            return true;
        }

        return false;
    }

    private static function logLegacyAliasUsage(string $legacyModule, string $canonicalModule, string $permission): void
    {
        $key = "{$legacyModule}|{$canonicalModule}|{$permission}";
        if (isset(self::$loggedLegacyAliases[$key])) {
            return;
        }

        self::$loggedLegacyAliases[$key] = true;

        Log::warning('Permission alias legacy utilise', [
            'legacy_module' => $legacyModule,
            'canonical_module' => $canonicalModule,
            'permission' => $permission,
            'strict_mode' => self::strictLegacyAliasesEnabled(),
        ]);
    }

}
