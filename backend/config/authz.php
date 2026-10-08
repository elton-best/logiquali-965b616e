<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Roles-only RBAC mode
    |--------------------------------------------------------------------------
    |
    | false: mode hybride (rôles + permissions directes)
    | true: mode strict (permissions directes interdites en écriture)
    |
    */
    'enforce_roles_only_permissions' => env('AUTHZ_ENFORCE_ROLES_ONLY_PERMISSIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Legacy permission aliases strict mode
    |--------------------------------------------------------------------------
    |
    | false: compat mode (alias legacy encore acceptés, avec logs de dépréciation)
    | true: strict mode (alias legacy non résolus vers le canonique)
    |
    */
    'strict_legacy_aliases' => env('AUTHZ_STRICT_LEGACY_ALIASES', true),

    /*
    |--------------------------------------------------------------------------
    | Shadow comparison mode
    |--------------------------------------------------------------------------
    |
    | When enabled, PermissionHelper compares legacy and canonical permission
    | decisions and logs only divergences. Access decision keeps current engine.
    |
    */
    'shadow_compare_enabled' => env('AUTHZ_SHADOW_COMPARE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Shadow comparison dimensions
    |--------------------------------------------------------------------------
    |
    | scope: compare explicit additional scope checks passed to PermissionHelper.
    | subscription: compare against active scoped permission set (site/subscription).
    |
    */
    'shadow_compare_include_scope' => env('AUTHZ_SHADOW_COMPARE_INCLUDE_SCOPE', true),
    'shadow_compare_include_subscription' => env('AUTHZ_SHADOW_COMPARE_INCLUDE_SUBSCRIPTION', true),

    /*
    |--------------------------------------------------------------------------
    | Enforce active scope in authorization decisions
    |--------------------------------------------------------------------------
    |
    | When enabled, a granted permission must also belong to the user's active
    | scoped permission set (subscription + site context + system prefixes).
    |
    */
    'enforce_active_scope' => env('AUTHZ_ENFORCE_ACTIVE_SCOPE', false),

    /*
    |--------------------------------------------------------------------------
    | Progressive enforcement cohorts
    |--------------------------------------------------------------------------
    |
    | If AUTHZ_ENFORCE_ACTIVE_SCOPE=false, enforcement can still be activated
    | for specific users/enterprises via cohort lists.
    |
    */
    'enforce_active_scope_cohort_user_ids' => array_values(array_filter(array_map(
        static fn ($value) => (int) trim((string) $value),
        explode(',', (string) env('AUTHZ_ENFORCE_ACTIVE_SCOPE_COHORT_USER_IDS', ''))
    ))),
    'enforce_active_scope_cohort_enterprise_ids' => array_values(array_filter(array_map(
        static fn ($value) => (int) trim((string) $value),
        explode(',', (string) env('AUTHZ_ENFORCE_ACTIVE_SCOPE_COHORT_ENTERPRISE_IDS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Legacy module aliases (compat mode only)
    |--------------------------------------------------------------------------
    |
    | Empty by default. If temporary backward compatibility is needed,
    | populate aliases and set AUTHZ_STRICT_LEGACY_ALIASES=false.
    |
    | Example:
    | 'legacy_module_aliases' => [
    |     'process' => 'processes',
    | ],
    |
    */
    'legacy_module_aliases' => [],

    /*
    |--------------------------------------------------------------------------
    | Legacy permission aliases (legacy -> canonical)
    |--------------------------------------------------------------------------
    |
    | This map is used to normalize permission checks to a canonical value.
    | Keep only temporary aliases here and remove them after migration.
    |
    */
    'permission_aliases' => [],

    /*
    |--------------------------------------------------------------------------
    | System permission prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes always retained in active scoped permission filtering and
    | active permission catalog endpoints, regardless of site subscriptions.
    |
    */
    'system_permission_prefixes' => [
        'users',
        'personnel',
        'roles',
        'leadership',
        'dashboard',
        'permissions',
        'settings',
        'search',
        'sessions',
        'notifications',
        'subscriptions',
        'security_audit',
        'evaluation',
        'performance',
        'amelioration',
        'non_conformities',
        'actions',
        'audits',
        'management_reviews',
        'indicateurs',
    ],
];
