import { computed, ref } from 'vue'
import { useAccessCatalog } from './useAccessCatalog'

const modules = ref<Module[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const loaded = ref(false)
let inflightPromise: Promise<void> | null = null

interface Module {
  id: number
  name: string
  code: string
  icon: string
  order: number
  is_active: boolean
}

function normalizeCode (code: string | null | undefined): string {
  return String(code || '').trim().toLowerCase()
}

export function useSubscribedModules () {
  const { fetchCatalog, modules: catalogModules, clearCatalogCache } = useAccessCatalog()

  const fetchModules = async (force = false) => {
    if (!force && loaded.value) {
      return
    }

    if (inflightPromise) {
      return inflightPromise
    }

    inflightPromise = (async () => {
      loading.value = true
      error.value = null

      try {
        await fetchCatalog(force)
        const dedupedByCode = new Map<string, Module>()
        for (const module of (catalogModules.value || [])) {
          const normalizedCode = normalizeCode(module.code)
          if (!normalizedCode) {
            continue
          }

          const current = dedupedByCode.get(normalizedCode)
          if (!current || module.order < current.order) {
            dedupedByCode.set(normalizedCode, {
              id: module.id,
              name: module.name,
              code: normalizedCode,
              icon: module.icon,
              order: module.order,
              is_active: module.is_active,
            })
          }
        }

        modules.value = [...dedupedByCode.values()].toSorted((a, b) => a.order - b.order)
        loaded.value = true
      } catch (error_: any) {
        error.value = error_.message || 'Erreur lors du chargement des modules'
        loaded.value = false
        console.error('❌ [useSubscribedModules] Error fetching modules:', error_)
      } finally {
        loading.value = false
        inflightPromise = null
      }
    })()

    return inflightPromise
  }

  const clearModulesCache = () => {
    clearCatalogCache()
    modules.value = []
    loaded.value = false
    error.value = null
    inflightPromise = null
  }

  const isModuleAccessible = (moduleIdOrCode: number | string): boolean => {
    const accessible = typeof moduleIdOrCode === 'number'
      ? modules.value.some(m => m.id === moduleIdOrCode)
      : modules.value.some(m => m.code === moduleIdOrCode)
    return accessible
  }

  const hasModule = (moduleName: string): boolean => {
    return modules.value.some(m => m.name.toLowerCase().includes(moduleName.toLowerCase()))
  }

  return {
    modules: computed(() => modules.value),
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    fetchModules,
    clearModulesCache,
    isModuleAccessible,
    hasModule,
  }
}
