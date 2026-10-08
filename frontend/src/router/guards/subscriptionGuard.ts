import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { subscriptionService } from '@/services/subscriptionService'
import { useAuthStore } from '@/stores/auth'
import { getBlockingState } from '@/utils/blockingAccess'

export async function subscriptionGuard (
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext,
) {
  const authStore = useAuthStore()
  const user = authStore.user

  try {
    const siteId = authStore.currentSiteId || (user as any)?.site_id

    if (!siteId) {
      return next()
    }

    const status = await subscriptionService.checkStatus(siteId)

    const hasAccess = Boolean(status.hasActiveSubscription || status.can_access_dashboard)
    // Non-blocking guard: access control by norm is handled in the shell/menu
    // so users can still reach dashboard and see grayed modules.
    if (!hasAccess) {
      return next()
    }

    return next()
  } catch (error: any) {
    const status = error?.status || error?.response?.status
    const code = error?.code || error?.response?.data?.code
    const backendRedirect = error?.response?.data?.redirect

    if (status === 423) {
      const blockingState = getBlockingState(code, user)
      const redirectPath = backendRedirect || blockingState?.redirectPath
      if (redirectPath) {
        return next(redirectPath)
      }
    }

    // En cas d'erreur non catégorisée, on laisse passer pour éviter un blocage total
    return next()
  }
}
