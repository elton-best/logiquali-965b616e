import type { Router } from 'vue-router'

export function getRoleName (role: any): string | null {
  if (typeof role === 'string') {
    return role
  }
  if (typeof role?.name === 'string') {
    return role.name
  }
  if (typeof role?.attributes?.name === 'string') {
    return role.attributes.name
  }
  return null
}

export function hasRole (user: any, roleName: string): boolean {
  const roleNames = Array.isArray(user?.role_names) ? user.role_names : []
  if (roleNames.includes(roleName)) {
    return true
  }

  if (typeof user?.access_role === 'string' && user.access_role === roleName) {
    return true
  }

  const roles = user?.roles
  if (!Array.isArray(roles)) {
    return false
  }

  return roles.some((role: any) => getRoleName(role) === roleName)
}

export function isEnterpriseAdminUser (user: any): boolean {
  if (!user || user.user_type !== 'company') {
    return false
  }

  return hasRole(user, 'admin_entreprise')
}

export function routePathExists (router: Router, path: string): boolean {
  const normalizedPath
    = (path.split(/[?#]/)[0] || '/').replace(/\/+$/, '') || '/'

  return router.getRoutes().some(route => {
    const routePath = (route.path || '/').replace(/\/+$/, '') || '/'
    if (routePath === normalizedPath) {
      return true
    }

    if (!routePath.includes(':')) {
      return false
    }

    const pattern = routePath.replace(/:[^/]+/g, '[^/]+')
    const regex = new RegExp(`^${pattern}$`)
    return regex.test(normalizedPath)
  })
}
