import type { Module, ModuleWithPermissions, SubModule, SubModuleSection, Subscription, SubscriptionStatus, TrialAlert } from '../types/subscription.types'
import api from '@/api/client'

const ACCESS_CATALOG_TTL_MS = 30_000
const accessCatalogCacheBySite = new Map<string, { data: any, expiresAt: number }>()
const accessCatalogInFlightBySite = new Map<string, Promise<any>>()

function getCurrentSiteId (): number | null {
  const raw = localStorage.getItem('current_site_id')
  if (!raw) {
    return null
  }

  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : null
}

async function getAccessCatalog (): Promise<any> {
  const siteId = getCurrentSiteId()
  const cacheKey = String(siteId || 'default')
  const cached = accessCatalogCacheBySite.get(cacheKey)
  if (cached && cached.expiresAt > Date.now()) {
    return cached.data
  }

  const inFlight = accessCatalogInFlightBySite.get(cacheKey)
  if (inFlight) {
    return inFlight
  }

  const request = api.get('/access/catalog', {
    params: siteId ? { site_id: siteId } : undefined,
    headers: { 'X-Skip-Error-Toast': 'true' },
  })
    .then(response => {
      const data = response.data || {}
      accessCatalogCacheBySite.set(cacheKey, {
        data,
        expiresAt: Date.now() + ACCESS_CATALOG_TTL_MS,
      })
      return data
    })
    .finally(() => {
      accessCatalogInFlightBySite.delete(cacheKey)
    })

  accessCatalogInFlightBySite.set(cacheKey, request)
  return request
}

export const subscriptionApi = {
  getCurrent: () => api.get<SubscriptionStatus>('/subscription/current'),

  subscribe: (data: { offer_id: number, site_id: number, is_trial?: boolean }) =>
    api.post<{ message: string, subscription: Subscription }>('/subscription/subscribe', data),

  getAlerts: () => api.get<{ alerts: TrialAlert[] }>('/subscription/alerts'),
}

export const modulesApi = {
  getAll: () => api.get<{ modules: Module[] }>('/modules'),

  getAccessible: async () => {
    const data = await getAccessCatalog()
    const modules = Array.isArray(data?.modules) ? data.modules : []
    const normalized: ModuleWithPermissions[] = modules.map(module => ({
      id: module.id,
      identifier: module.code,
      name: module.name,
      description: module.description,
      category: 'qualite',
      route: `/company/${module.code}`,
      icon: module.icon || 'mdi-view-grid-outline',
      order: module.order ?? 0,
      is_active: module.is_active ?? true,
      permissions: {
        read: Boolean(module.permissions?.read ?? module.permissions?.access ?? true),
        create: Boolean(module.permissions?.create),
        update: Boolean(module.permissions?.update),
        delete: Boolean(module.permissions?.delete),
        validate: Boolean(module.permissions?.validate ?? module.permissions?.approve),
      },
    }))

    return { data: { modules: normalized } }
  },
}

export const subModulesApi = {
  getAll: () => api.get<{ data: SubModule[] }>('/sub-modules'),

  getAccessible: async () => {
    const data = await getAccessCatalog()
    const subModules = Array.isArray(data?.sub_modules) ? data.sub_modules : []
    return { data: { data: subModules } }
  },
}

export const sectionsApi = {
  getAll: () => api.get<{ data: SubModuleSection[] }>('/sub-module-sections'),

  getAccessible: async () => {
    const data = await getAccessCatalog()
    const sections = Array.isArray(data?.sections) ? data.sections : []
    return { data: { data: sections } }
  },
}

export const permissionsApi = {
  getUserPermissions: (userId: number) =>
    api.get<{ permissions: string[] }>(`/users/${userId}/permissions`),

  updateUserPermissions: (userId: number, permissions: string[]) =>
    api.put<{ message: string, permissions: string[] }>(`/users/${userId}/permissions`, { permissions }),

  getAvailable: () =>
    api.get<{ permissions: Array<{ module: string, identifier: string, permissions: string[] }> }>('/permissions/available'),
}
