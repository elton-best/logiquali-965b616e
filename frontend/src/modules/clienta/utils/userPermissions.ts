import { normalizePermissionList } from '@/utils/permissions'

type PermissionLike = string | { name?: string, attributes?: { name?: string } } | null | undefined

export function extractPermissionNames (permissions: PermissionLike[]): string[] {
  return permissions
    .map(permission => {
      if (typeof permission === 'string') {
        return permission
      }
      if (typeof permission?.name === 'string') {
        return permission.name
      }
      if (typeof permission?.attributes?.name === 'string') {
        return permission.attributes.name
      }
      return null
    })
    .filter(Boolean) as string[]
}

export function getEffectivePermissionNames (user: any): string[] {
  const enforceActiveScope = Boolean(user?.authz_enforce_active_scope)

  // Source de vérité canonique:
  // 1) active_scoped_permissions (norme + scope site + rôle + overrides)
  // 2) effective_permissions
  // 3) all_permissions (fallback legacy uniquement)
  const activeScopedPermissions = normalizePermissionList(
    extractPermissionNames(
      Array.isArray(user?.active_scoped_permissions)
        ? user.active_scoped_permissions
        : [],
    ),
  )
  if (activeScopedPermissions.length > 0) {
    return activeScopedPermissions
  }

  if (enforceActiveScope) {
    return []
  }

  const effectivePermissions = normalizePermissionList(
    extractPermissionNames(
      Array.isArray(user?.effective_permissions)
        ? user.effective_permissions
        : [],
    ),
  )
  if (effectivePermissions.length > 0) {
    return effectivePermissions
  }

  const legacyPermissions = normalizePermissionList(
    extractPermissionNames(
      Array.isArray(user?.all_permissions) ? user.all_permissions : [],
    ),
  )
  return legacyPermissions
}

export function getEffectivePermissionSet (user: any): Set<string> {
  return new Set(getEffectivePermissionNames(user))
}

export function getNavigationPermissionNames (user: any): string[] {
  const hasAssignedActions = Boolean(user?.has_assigned_actions)
  const actionOverlayPermissions = hasAssignedActions ? ['amelioration.non_conformites.read'] : []

  return normalizePermissionList([
    ...getEffectivePermissionNames(user),
    ...actionOverlayPermissions,
  ])
}

export function getNavigationPermissionSet (user: any): Set<string> {
  return new Set(getNavigationPermissionNames(user))
}
