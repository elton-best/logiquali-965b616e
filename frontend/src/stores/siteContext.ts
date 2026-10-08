/**
 * Site Context Store (Pinia)
 * Store global pour le site actif - Option A
 */

import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import api from '@/api/client'
import { normalizePermissionList } from '@/utils/permissions'
import { useAuthStore } from './auth'

export interface SiteOption {
  value: string | number
  title: string
  hasSubscription?: boolean
}

export type SiteScope = 'site' | 'enterprise'

export const useSiteContextStore = defineStore('siteContext', () => {
  const authStore = useAuthStore()
  const SITES_CACHE_TTL_MS = 30_000

  function hasRole (roleName: string): boolean {
    const user = authStore.user as any
    if (!user) {
      return false
    }

    const roleNames = Array.isArray(user.role_names) ? user.role_names : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = Array.isArray(user.roles) ? user.roles : []
    return roles.some(
      (role: any) =>
        role?.name === roleName
        || role?.attributes?.name === roleName
        || role === roleName,
    )
  }

  function getEffectivePermissionSet (): Set<string> {
    const user = authStore.user as any
    if (!user) {
      return new Set()
    }

    const fromDirect = Array.isArray(user.permissions) ? user.permissions : []
    const fromEffective = Array.isArray(user.effective_permissions)
      ? user.effective_permissions
      : []
    const fromActiveScoped = Array.isArray(user.active_scoped_permissions)
      ? user.active_scoped_permissions
      : []
    let fromRolePermissions: any[] = []
    if (Array.isArray(user.roles)) {
      fromRolePermissions = user.roles.flatMap((role: any) => {
        if (Array.isArray(role?.permissions)) {
          return role.permissions
        }
        if (Array.isArray(role?.relationships?.permissions)) {
          return role.relationships.permissions
        }
        return []
      })
    }

    const names = [
      ...fromDirect,
      ...fromEffective,
      ...fromActiveScoped,
      ...fromRolePermissions,
    ]
      .map((permission: any) => {
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

    return new Set(normalizePermissionList(names))
  }

  function hasPermission (permissionName: string): boolean {
    const normalized = String(permissionName || '').trim().toLowerCase()
    if (!normalized) {
      return false
    }
    return getEffectivePermissionSet().has(normalized)
  }

  // State
  const activeSiteId = ref<string | number | null>(null)
  const activeScope = ref<SiteScope>('site')
  const availableSites = ref<SiteOption[]>([])
  const loading = ref(false)
  const lastLoadedAt = ref<number | null>(null)
  let loadSitesPromise: Promise<void> | null = null

  // Getters
  const activeSite = computed(
    () =>
      availableSites.value.find(
        s => String(s.value) === String(activeSiteId.value),
      ) || null,
  )

  const hasMultipleSites = computed(() => availableSites.value.length > 1)

  function normalizeSubscriptions (site: any): any[] {
    const siteData = site?.attributes || site || {}

    const fromAttributes = Array.isArray(siteData.subscriptions)
      ? siteData.subscriptions
      : []
    if (fromAttributes.length > 0) {
      return fromAttributes
    }

    const relationships = site?.relationships || {}
    const rawSubscriptions = relationships.subscriptions
    if (Array.isArray(rawSubscriptions)) {
      return rawSubscriptions
    }
    if (Array.isArray(rawSubscriptions?.data)) {
      return rawSubscriptions.data
    }
    if (
      rawSubscriptions
      && typeof rawSubscriptions === 'object'
      && rawSubscriptions.id
    ) {
      return [rawSubscriptions]
    }

    const legacySubscription = relationships.subscription
    if (
      legacySubscription
      && typeof legacySubscription === 'object'
      && legacySubscription.id
    ) {
      return [legacySubscription]
    }

    return []
  }

  function hasActiveCoverage (subscriptions: any[]): boolean {
    if (!Array.isArray(subscriptions) || subscriptions.length === 0) {
      return false
    }

    return subscriptions.some(sub => {
      const attrs = sub?.attributes || sub || {}
      const isActive = attrs.is_active === true
      const status = String(attrs.status || '').toLowerCase()

      return (
        isActive && (status === 'active' || status === 'trial' || status === '')
      )
    })
  }

  // Actions
  async function loadAvailableSites (force = false) {
    const recentlyLoaded
      = lastLoadedAt.value !== null
        && Date.now() - lastLoadedAt.value < SITES_CACHE_TTL_MS

    if (!force && recentlyLoaded && availableSites.value.length > 0) {
      return
    }

    if (loadSitesPromise) {
      return loadSitesPromise
    }

    loadSitesPromise = (async () => {
      try {
        loading.value = true
        const authSites = Array.isArray(authStore.availableSites)
          ? authStore.availableSites
          : []
        const canLoadEnterpriseSites
          = (authStore.user as any)?.user_type === 'company'
            && (hasRole('admin_entreprise') || hasPermission('sites.read'))
        let sites = authSites

        if (canLoadEnterpriseSites || sites.length === 0) {
          const response = await api.get('/sites')
          sites = response.data?.data || []
        }

        if (canLoadEnterpriseSites) {
          authStore.setAvailableSites(sites)
        }

        availableSites.value = sites.map((site: any) => {
          const siteData = site.attributes || site
          const subscriptions = normalizeSubscriptions(site)
          const hasActiveSubscription = hasActiveCoverage(subscriptions)

          return {
            value: site.id || siteData.id,
            title: siteData.name || `Site #${site.id}`,
            hasSubscription: hasActiveSubscription,
          }
        })

        // Initialiser avec le site de l'utilisateur ou le premier site
        if (!activeSiteId.value && availableSites.value.length > 0) {
          const userSiteId = authStore.currentSiteId
          const defaultSite = userSiteId
            ? availableSites.value.find(
                s => String(s.value) === String(userSiteId),
              )
            : availableSites.value[0]

          if (defaultSite) {
            setActiveSite(defaultSite.value, false) // false = ne pas recharger
          }
        }

        if (activeScope.value === 'site' && activeSiteId.value) {
          const exists = availableSites.value.some(
            site => String(site.value) === String(activeSiteId.value),
          )
          if (!exists) {
            const fallback = availableSites.value[0]
            if (fallback) {
              setActiveSite(fallback.value, false)
            }
          }
        }

        lastLoadedAt.value = Date.now()
      } catch (error) {
        console.error('Erreur chargement sites:', error)
        availableSites.value = []
      } finally {
        loading.value = false
        loadSitesPromise = null
      }
    })()

    return loadSitesPromise
  }

  function setActiveSite (siteId: string | number | null, persist = true) {
    activeScope.value = 'site'
    activeSiteId.value = siteId

    if (persist && siteId) {
      localStorage.setItem('active_site_id', String(siteId))

      // Synchroniser avec authStore si nécessaire
      const numericSiteId = Number(siteId)
      if (Number.isFinite(numericSiteId)) {
        authStore.setCurrentSite(numericSiteId)
      }
    }
  }

  function setEnterpriseScope (persist = true) {
    activeScope.value = 'enterprise'
    activeSiteId.value = null

    authStore.clearCurrentSite()

    if (persist) {
      localStorage.removeItem('active_site_id')
    }
  }

  function initFromStorage () {
    const stored = localStorage.getItem('active_site_id')
    if (stored) {
      activeScope.value = 'site'
      activeSiteId.value = stored
      return
    }

    activeScope.value = 'enterprise'
  }

  watch(
    [activeScope, activeSiteId],
    ([newScope, newSiteId], [oldScope, oldSiteId]) => {
      const scopeChanged = newScope !== oldScope
      const siteChanged = String(newSiteId) !== String(oldSiteId)

      if ((!scopeChanged && !siteChanged) || typeof window === 'undefined') {
        return
      }

      window.dispatchEvent(
        new CustomEvent('site-context-changed', {
          detail: {
            scope: newScope,
            siteId: newSiteId,
            previousScope: oldScope,
            previousSiteId: oldSiteId,
          },
        }),
      )
    },
  )

  return {
    // State
    activeSiteId,
    activeScope,
    availableSites,
    loading,

    // Getters
    activeSite,
    hasMultipleSites,

    // Actions
    loadAvailableSites,
    setActiveSite,
    setEnterpriseScope,
    initFromStorage,
  }
})
