/**
 * Router Navigation Guards
 * Handles authentication and authorization checks
 */

import type { Router } from 'vue-router'
import { watch } from 'vue'
import {
  isPublicSeoRoute,
  updateDocumentLanguageOnly,
  updateSeoForRoute,
} from '@/composables/useSeo'
import i18n from '@/i18n'
import { useAccessCatalog } from '@/modules/clienta/composables/useAccessCatalog'
import { hasReadAccess } from '@/modules/clienta/utils/accessPermissions'
import { canonicalizeCompanyRoute } from '@/modules/clienta/utils/routeCanonicalizer'
import { getNavigationPermissionNames } from '@/modules/clienta/utils/userPermissions'
import { useAuthStore } from '@/stores/auth'
import { useLockScreenStore } from '@/stores/lockScreen'
import { isEnterpriseAdminUser } from '@/utils/accessControl'
import {
  getBlockingState,
  inferBlockingCodeFromUser,
} from '@/utils/blockingAccess'
import {
  expandPermissionAliases,
  normalizePermissionList,
} from '@/utils/permissions'
import { subscriptionGuard } from './guards/subscriptionGuard'

/**
 * Public routes that don't require authentication
 */
const PUBLIC_ROUTES = [
  '/',
  '/landing',
  '/pricing',
  '/guide-utilisation',
  '/politique-confidentialite',
  '/evaluation',
  '/auth/login',
  '/auth/signup',
  '/auth/signup/company',
  '/auth/signup/clientb',
  '/auth/signup/individual',
  '/auth/signup-loading',
  '/auth/signup-success',
  '/auth/forgot-password',
  '/auth/reset-password',
  '/auth/email-verification',
  '/auth/email-verified',
  '/auth/email-verification-failed',
  '/auth/pending-approval',
  '/terms',
  '/privacy',
  '/403',
]

const SUBSCRIPTION_GUARD_BYPASS_PREFIXES = [
  '/company/subscription',
  '/company/settings',
]

const COLLABORATOR_COMPANY_ALLOWLIST_PREFIXES = [
  '/company/dashboard',
  '/company/settings',
  '/company/subscription',
]

function isCollaboratorCompanyAllowlistedPath (path: string): boolean {
  return COLLABORATOR_COMPANY_ALLOWLIST_PREFIXES.some(
    prefix => path === prefix || path.startsWith(`${prefix}/`),
  )
}

let isSeoLocaleWatcherInitialized = false

/**
 * Check if user has required permission
 * @param permissions - Array of permissions from route meta
 * @param userPermissions - User's permissions from auth store
 * @returns boolean
 */
function hasPermission (
  permissions: string[] | undefined,
  userPermissions: any[] = [],
): boolean {
  // No permissions required
  if (!permissions || permissions.length === 0) {
    return true
  }

  // Extract permission names from Permission objects if needed
  const permissionNames = new Set(
    normalizePermissionList(
      userPermissions.map(p => (typeof p === 'string' ? p : p?.name)),
    ),
  )

  // Check if user has at least one of the required permissions
  return permissions.some(permission => {
    const acceptedAliases = expandPermissionAliases(permission)
    return acceptedAliases.some(alias => permissionNames.has(alias))
  })
}

function normalizeRouteForMatch (rawRoute: string): string {
  const canonical = canonicalizeCompanyRoute(rawRoute, rawRoute)
  const [baseWithQuery = ''] = canonical.split('#')
  const [basePath = ''] = baseWithQuery.split('?')
  const trimmed
    = basePath.endsWith('/') && basePath !== '/'
      ? basePath.slice(0, -1)
      : basePath
  return trimmed || '/'
}

async function canAccessCatalogRoute (
  fullPath: string,
): Promise<boolean | null> {
  const { fetchCatalog, sections, subModules } = useAccessCatalog()
  const target = normalizeRouteForMatch(fullPath)

  try {
    await fetchCatalog()
  } catch {
    return null
  }

  const section = sections.value.find(
    entry => normalizeRouteForMatch(entry.route || '') === target,
  )
  if (section) {
    return hasReadAccess(section.permissions)
  }

  const subModule = subModules.value.find(
    entry => normalizeRouteForMatch(entry.route || '') === target,
  )
  if (subModule) {
    return hasReadAccess(subModule.permissions)
  }

  return null
}

function resolveCompanyBlockingRedirect (
  user: any,
  targetPath: string,
  targetFullPath: string,
): string | null {
  if (!user || user.user_type !== 'company') {
    return null
  }

  const blockingCode = inferBlockingCodeFromUser(user) || undefined
  const blockingState = getBlockingState(blockingCode, user)
  if (!blockingState) {
    return null
  }

  if (blockingState.redirectPath === targetFullPath) {
    return null
  }

  const redirectPath = blockingState.redirectPath.split('?')[0]
  if (redirectPath === targetPath && targetFullPath.includes('blocking=')) {
    return null
  }

  return blockingState.redirectPath
}

/**
 * Setup navigation guards
 */
export function setupGuards (router: Router) {
  // eslint-disable-next-line complexity
  router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()
    const lockScreenStore = useLockScreenStore()
    const isAuthenticated = authStore.isAuthenticated
    const forceLogin
      = to.path === '/auth/login' && String(to.query.force_login || '') === '1'
    const isPasswordResetRoute
      = to.path === '/auth/reset-password' || to.path === '/reset-password'

    // Check if session is locked and trying to access a route other than lock-screen
    if (lockScreenStore.isLocked && to.path !== '/lock-screen') {
      return next('/lock-screen')
    }

    // Check if route is public
    const isPublicRouteByMeta = to.meta?.public === true
    const isPublicRouteByPath = PUBLIC_ROUTES.some(route => {
      if (route === '/') {
        return to.path === '/'
      }

      return to.path === route || to.path.startsWith(`${route}/`)
    })
    const isPublicRoute = isPublicRouteByMeta || isPublicRouteByPath

    // If not authenticated and trying to access protected route
    if (!isAuthenticated && !isPublicRoute) {
      return next({
        path: '/auth/login',
        query: { redirect: to.fullPath },
      })
    }

    // If authenticated and trying to access auth pages (except verification-result pages), redirect to dashboard.
    // Keep /auth/email-verified and /auth/email-verification-failed reachable even with an active session.
    if (
      isAuthenticated
      && to.path.startsWith('/auth/')
      && to.path !== '/auth/pending-approval'
      && to.path !== '/auth/email-verification'
      && to.path !== '/auth/email-verified'
      && to.path !== '/auth/email-verification-failed'
      && to.path !== '/auth/force-password-change'
      && !isPasswordResetRoute
      && !forceLogin
    ) {
      const dashboardRoute = authStore.getDashboardRoute()
      if (dashboardRoute !== to.path) {
        return next(dashboardRoute)
      }
    }

    // Check role-based access
    if (isAuthenticated && authStore.user) {
      const userType = authStore.user.user_type
      const mustChangePassword = !!(authStore.user as any)
        ?.must_change_password

      if (to.path.startsWith('/employee')) {
        if (userType === 'company') {
          return next('/company/dashboard')
        }
        return next('/403')
      }

      // SuperAdmin routes
      if (to.path.startsWith('/superadmin') && userType !== 'super_admin') {
        return next('/403')
      }

      // Company routes (Enterprise users)
      if (to.path.startsWith('/company') && userType !== 'company') {
        return next('/403')
      }

      // Unified blocking flow for company users
      const blockingRedirect = resolveCompanyBlockingRedirect(
        authStore.user,
        to.path,
        to.fullPath,
      )
      if (blockingRedirect) {
        return next(blockingRedirect)
      }

      // Enterprise approval check - block access if not approved
      // IMPORTANT: Allow access to /auth/pending-approval even if enterprise is not active
      if (
        userType === 'company'
        && to.path.startsWith('/company')
        && !to.path.startsWith('/auth/')
      ) {
        const enterprise = authStore.user.enterprise

        const enterpriseStatus = String(
          (enterprise as any)?.status || '',
        ).toLowerCase()
        if (
          enterprise
          && enterpriseStatus !== 'active'
          && enterpriseStatus !== 'approved'
        ) {
          // Clear auth and redirect to pending approval page
          authStore.clearAuth()
          return next({
            path: '/auth/pending-approval',
            query: { status: enterpriseStatus || 'pending' },
          })
        }
      }

      // Client B routes
      if (to.path.startsWith('/clientb') && userType !== 'clientb') {
        return next('/403')
      }

      // ========== PERMISSION-BASED ACCESS (SPATIE) ==========
      // Check if route requires specific permissions (plural or singular)
      const requiredPermissions = Array.isArray(to.meta.permissions)
        ? to.meta.permissions
        : (to.meta.permission
            ? [to.meta.permission]
            : null)

      if (requiredPermissions && Array.isArray(requiredPermissions)) {
        if (userType === 'company' && isEnterpriseAdminUser(authStore.user)) {
          return next()
        }

        const userPermissions = getNavigationPermissionNames(authStore.user)

        if (
          !hasPermission(requiredPermissions as string[], userPermissions)
        ) {
          return next('/403')
        }
      }

      // ========== CATALOG-BASED ACCESS (SUBMODULES/SECTIONS) ==========
      // If no explicit permission metadata, fall back to access catalog rules (site + subscription + role).
      if (
        userType === 'company'
        && to.path.startsWith('/company')
        && (!Array.isArray(requiredPermissions)
          || requiredPermissions.length === 0)
      ) {
        const isCollaborator = !isEnterpriseAdminUser(authStore.user)
        const catalogAccess = await canAccessCatalogRoute(to.fullPath)
        if (catalogAccess === false) {
          return next('/403')
        }
        if (catalogAccess === null && isCollaborator) {
          const isAllowlisted = isCollaboratorCompanyAllowlistedPath(to.path)
          if (!isAllowlisted) {
            return next('/403')
          }
        }
      }

      // ========== SUBSCRIPTION CHECK ==========
      // Check subscription for company routes (except subscription page itself)
      const shouldRunSubscriptionGuard
        = isAuthenticated
          && to.path.startsWith('/company')
          && !mustChangePassword
          && !SUBSCRIPTION_GUARD_BYPASS_PREFIXES.some(prefix =>
            to.path.startsWith(prefix),
          )

      if (shouldRunSubscriptionGuard) {
        await subscriptionGuard(to, from, next)
        return
      }
    }

    next()
  })

  if (!isSeoLocaleWatcherInitialized) {
    watch(
      () => i18n.global.locale.valueOf(),
      () => {
        const currentRoute = router.currentRoute.value
        if (isPublicSeoRoute(currentRoute)) {
          updateSeoForRoute(currentRoute)
        } else {
          updateDocumentLanguageOnly()
        }
      },
      { immediate: true },
    )
    isSeoLocaleWatcherInitialized = true
  }

  // After navigation - update document title and SEO metadata for public pages
  router.afterEach(to => {
    if (isPublicSeoRoute(to)) {
      updateSeoForRoute(to)
      return
    }

    const title = (to.meta.title as string) || 'BestQHSE'
    document.title = `${title} | BestQHSE`
    updateDocumentLanguageOnly()
  })
}
