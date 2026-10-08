import { computed, ref } from 'vue'
import api from '@/api/client'

type CatalogModule = {
  id: number
  code: string
  name: string
  description?: string
  icon: string
  order: number
  is_active: boolean
  permissions?: Record<string, boolean>
}

type CatalogSubModule = {
  id: number
  module_id: number
  module_code: string
  module_name: string
  code: string
  name: string
  description?: string
  icon: string
  route: string
  order: number
  is_active: boolean
  permissions?: Record<string, boolean>
}

type CatalogSection = {
  id: number
  sub_module_id: number
  sub_module_code: string
  sub_module_name: string
  module_code: string
  module_name: string
  code: string
  name: string
  description?: string
  icon: string
  route: string
  order: number
  is_active: boolean
  permissions?: Record<string, boolean>
}

type CatalogNorm = {
  id: number
  code: string
  name: string
  domain?: string
  status?: 'draft' | 'published' | 'archived' | string
}

type AccessCatalog = {
  norms: CatalogNorm[]
  modules: CatalogModule[]
  sub_modules: CatalogSubModule[]
  sections: CatalogSection[]
  meta?: Record<string, any>
}

const catalog = ref<AccessCatalog>({
  norms: [],
  modules: [],
  sub_modules: [],
  sections: [],
  meta: {},
})

const loading = ref(false)
const error = ref<string | null>(null)
const loaded = ref(false)
const loadedSiteId = ref<number | null>(null)
let inflightPromise: Promise<void> | null = null
const CATALOG_TTL_MS = 30_000
const loadedAt = ref<number | null>(null)

function normalizeCode (code: string | null | undefined): string {
  return String(code || '').trim().toLowerCase()
}

function dedupeBy<T> (items: T[], keyFactory: (item: T) => string): T[] {
  const map = new Map<string, T>()
  for (const item of items) {
    const key = keyFactory(item)
    if (!key) {
      continue
    }
    if (!map.has(key)) {
      map.set(key, item)
    }
  }
  return [...map.values()]
}

function getCurrentSiteId (): number | null {
  const raw = localStorage.getItem('current_site_id')
  if (!raw) {
    return null
  }
  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : null
}

export function useAccessCatalog () {
  const fetchCatalog = async (force = false) => {
    const siteId = getCurrentSiteId()
    const recentlyLoaded = loadedAt.value !== null && (Date.now() - loadedAt.value) < CATALOG_TTL_MS

    if (!force && loaded.value && loadedSiteId.value === siteId && recentlyLoaded) {
      return
    }

    if (inflightPromise) {
      return inflightPromise
    }

    inflightPromise = (async () => {
      loading.value = true
      error.value = null

      try {
        const response = await api.get('/access/catalog', {
          params: siteId ? { site_id: siteId } : undefined,
          headers: { 'X-Skip-Error-Toast': 'true' },
        })
        const data = response?.data || response || {}

        const norms = Array.isArray(data.norms) ? data.norms : []
        const modules = Array.isArray(data.modules) ? data.modules : []
        const subModules = Array.isArray(data.sub_modules) ? data.sub_modules : []
        const sections = Array.isArray(data.sections) ? data.sections : []

        catalog.value = {
          norms: dedupeBy(norms, norm => normalizeCode(norm.code)),
          modules: dedupeBy(modules, module => normalizeCode(module.code)),
          sub_modules: dedupeBy(
            subModules,
            subModule => `${normalizeCode(subModule.module_code)}::${normalizeCode(subModule.code)}`,
          ),
          sections: dedupeBy(
            sections,
            section => `${normalizeCode(section.module_code)}::${normalizeCode(section.sub_module_code)}::${normalizeCode(section.code)}`,
          ),
          meta: data.meta || {},
        }

        loaded.value = true
        loadedSiteId.value = siteId
        loadedAt.value = Date.now()
      } catch (error_: any) {
        error.value = error_?.message || 'Erreur lors du chargement du catalogue'
        loaded.value = false
        throw error_
      } finally {
        loading.value = false
        inflightPromise = null
      }
    })()

    return inflightPromise
  }

  const clearCatalogCache = () => {
    catalog.value = { norms: [], modules: [], sub_modules: [], sections: [], meta: {} }
    loaded.value = false
    loadedSiteId.value = null
    loadedAt.value = null
    error.value = null
    inflightPromise = null
  }

  return {
    catalog: computed(() => catalog.value),
    norms: computed(() => catalog.value.norms),
    modules: computed(() => catalog.value.modules),
    subModules: computed(() => catalog.value.sub_modules),
    sections: computed(() => catalog.value.sections),
    meta: computed(() => catalog.value.meta || {}),
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    loaded: computed(() => loaded.value),
    loadedSiteId: computed(() => loadedSiteId.value),
    fetchCatalog,
    clearCatalogCache,
  }
}
