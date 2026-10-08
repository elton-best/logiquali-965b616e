import { getNavigationPermissionNames } from '@/modules/clienta/utils/userPermissions'

export const CUSTOM_ROLE_PLACEHOLDER = '__custom__'

export function canManageCustomRoleForActor (user: any): boolean {
  if (!user) {
    return false
  }

  if (user.user_type === 'super_admin') {
    return true
  }

  const permissionSet = new Set(getNavigationPermissionNames(user))
  return permissionSet.has('roles.create')
}

export function sanitizeSelectedRole (params: {
  selectedRole: string
  canManageCustomRole: boolean
  fallbackRole?: string
}): string {
  const normalizedSelectedRole = String(params.selectedRole || '').trim()
  if (
    normalizedSelectedRole === CUSTOM_ROLE_PLACEHOLDER
    && !params.canManageCustomRole
  ) {
    return String(params.fallbackRole || '').trim()
  }

  return normalizedSelectedRole
}
